<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Actions\FlatpackBulkActionContext;
use Flatpack\Composition\EntityComposition;
use Flatpack\Http\Requests\ListRecordUpdateRequest;
use Flatpack\Services\Actions\ActionRuntime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

final readonly class EntityActionController
{
    public function __construct(
        private EntityComposition $entityComposition,
        private ActionRuntime $actions,
    ) {}

    public function bulkAction(Request $request, string $entity): RedirectResponse
    {
        $list = $this->entityComposition->listFor($entity);
        $schema = $this->entityComposition->listSchema($entity);
        $action = trim((string) $request->input('action', ''));
        if ($action === '') {
            abort(404, 'Flatpack bulk action is missing.');
        }

        $handler = $this->actions->resolveBulkActionHandler($action);
        $result = $handler->handle(FlatpackBulkActionContext::fromRequest(
            request: $request,
            entity: $entity,
            modelClass: (string) ($list->model ?? ''),
            schema: $schema,
        ));

        return back(303)->with('flatpack', [
            $action => (int) $result,
        ]);
    }

    public function listAction(Request $request, string $entity): RedirectResponse
    {
        $action = trim((string) $request->input('action', ''));
        if ($action === '') {
            abort(404, 'Flatpack list action is missing.');
        }

        [$listModelClass, $schema] = $this->listModelAndSchema($entity);
        $handler = $this->actions->resolveRecordActionHandler($action);
        try {
            $result = $handler->handle(new FlatpackActionContext(
                request: $request,
                entity: $entity,
                actionName: $action,
                modelClass: $listModelClass,
                record: null,
                compositionType: 'list',
                composition: $schema ?? [],
                schema: $schema,
                model: null,
            ));
        } catch (Throwable $exception) {
            throw $this->actions->toUserFacingValidationException($exception);
        }

        if ($result instanceof RedirectResponse) {
            return $result->setStatusCode(303);
        }

        return back(303)->with('flatpack', [
            $action => true,
        ]);
    }

    public function rowAction(
        Request $request,
        string $entity,
        string $record,
    ): RedirectResponse {
        $action = trim((string) $request->input('action', ''));
        if ($action === '') {
            abort(404, 'Flatpack row action is missing.');
        }

        [$listModelClass, $schema] = $this->listModelAndSchema($entity);
        $model = $this->actions->resolveRecordModel($listModelClass, $record, 'list');
        $handler = $this->actions->resolveRecordActionHandler($action);
        try {
            $result = $handler->handle(new FlatpackActionContext(
                request: $request,
                entity: $entity,
                actionName: $action,
                modelClass: $listModelClass,
                record: $record,
                compositionType: 'list',
                composition: $schema ?? [],
                schema: $schema,
                model: $model,
            ));
        } catch (Throwable $exception) {
            throw $this->actions->toUserFacingValidationException($exception);
        }

        if ($result instanceof RedirectResponse) {
            return $result->setStatusCode(303);
        }

        return back(303)->with('flatpack', [
            $action => true,
        ]);
    }

    public function updateRecord(
        ListRecordUpdateRequest $request,
        string $entity,
        string $record,
    ): RedirectResponse {
        [$listModelClass, $schema] = $this->listModelAndSchema($entity);
        $model = $this->actions->resolveRecordModel($listModelClass, $record, 'list');
        $handler = $this->actions->resolveRecordActionHandler('save');
        try {
            $handler->handle(new FlatpackActionContext(
                request: $request,
                entity: $entity,
                actionName: 'save',
                modelClass: $listModelClass,
                record: $record,
                compositionType: 'list',
                composition: $schema ?? [],
                schema: $schema,
                model: $model,
            ));
        } catch (Throwable $exception) {
            throw $this->actions->toUserFacingValidationException($exception);
        }

        return back(303)->with('flatpack', [
            'save' => true,
        ]);
    }

    /**
     * @return array{string, array<string, mixed>|null}
     */
    private function listModelAndSchema(string $entity): array
    {
        $list = $this->entityComposition->listFor($entity);
        $schema = $this->entityComposition->listSchema($entity);

        return [(string) ($list->model ?? ''), $schema];
    }
}
