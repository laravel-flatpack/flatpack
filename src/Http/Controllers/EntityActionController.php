<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Actions\FlatpackBulkActionContext;
use Flatpack\Composition\EntityComposition;
use Flatpack\Http\Requests\BulkActionRequest;
use Flatpack\Http\Requests\ListActionRequest;
use Flatpack\Http\Requests\ListRecordUpdateRequest;
use Flatpack\Support\ActionRuntime;
use Flatpack\Support\Exceptions\ActionRuntimeException;
use Flatpack\Support\SuccessRedirect;
use Flatpack\Support\SuccessRedirectSchema;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class EntityActionController
{
    public function __construct(
        private EntityComposition $entityComposition,
        private ActionRuntime $actions,
    ) {}

    public function bulkAction(BulkActionRequest $request, string $entity): RedirectResponse
    {
        $list = $this->entityComposition->listFor($entity);
        $schema = $this->entityComposition->listSchema($entity);
        $action = trim((string) $request->input('action', ''));
        if ($action === '') {
            abort(404, 'Flatpack bulk action is missing.');
        }

        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $handler = $this->actions->resolveBulkActionHandler($action);
        } catch (ActionRuntimeException $exception) {
            abort($exception->statusCode(), $exception->getMessage());
        }
        $this->actions->ensureBulkActionAuthorized($handler, $user, trim((string) ($list->model ?? '')));
        $result = $handler->handle(FlatpackBulkActionContext::fromRequest(
            request: $request,
            entity: $entity,
            modelClass: (string) ($list->model ?? ''),
            schema: $schema,
        ));

        $target = SuccessRedirectSchema::findForBulkAction($schema, $action);
        if ($target !== null) {
            return SuccessRedirect::responseForEntityAction($target, $entity, null)->with('flatpack', [
                $action => (int) $result,
            ]);
        }

        return back(303)->with('flatpack', [
            $action => (int) $result,
        ]);
    }

    public function listAction(ListActionRequest $request, string $entity): RedirectResponse
    {
        $action = trim((string) $request->input('action', ''));
        if ($action === '') {
            abort(404, 'Flatpack list action is missing.');
        }

        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        [$listModelClass, $schema] = $this->listModelAndSchema($entity);
        try {
            $handler = $this->actions->resolveRecordActionHandler($action);
        } catch (ActionRuntimeException $exception) {
            abort($exception->statusCode(), $exception->getMessage());
        }
        $this->actions->ensureRecordActionAuthorized($handler, $user, $listModelClass, null);
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
        } catch (AuthorizationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw $this->actions->toUserFacingValidationException($exception);
        }

        if ($result instanceof RedirectResponse) {
            return $result->setStatusCode(303);
        }

        $target = SuccessRedirectSchema::findForListHeaderAction($schema, $action);
        if ($target !== null) {
            return SuccessRedirect::responseForEntityAction($target, $entity, null)->with('flatpack', [
                $action => true,
            ]);
        }

        return back(303)->with('flatpack', [
            $action => true,
        ]);
    }

    public function rowAction(
        ListActionRequest $request,
        string $entity,
        string $record,
    ): RedirectResponse {
        $action = trim((string) $request->input('action', ''));
        if ($action === '') {
            abort(404, 'Flatpack row action is missing.');
        }

        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        [$listModelClass, $schema] = $this->listModelAndSchema($entity);
        try {
            $model = $this->actions->resolveRecordModel($listModelClass, $record, 'list');
            $handler = $this->actions->resolveRecordActionHandler($action);
        } catch (ActionRuntimeException $exception) {
            abort($exception->statusCode(), $exception->getMessage());
        }
        $this->actions->ensureRecordActionAuthorized($handler, $user, $listModelClass, $model);
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
        } catch (AuthorizationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw $this->actions->toUserFacingValidationException($exception);
        }

        if ($result instanceof RedirectResponse) {
            return $result->setStatusCode(303);
        }

        $formSchema = $this->entityComposition->formSchema($entity);
        $target = SuccessRedirectSchema::findForRowAction($formSchema, $schema, $action);
        if ($target !== null) {
            return SuccessRedirect::responseForEntityAction($target, $entity, $record)->with('flatpack', [
                $action => true,
            ]);
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
        try {
            $model = $this->actions->resolveRecordModel($listModelClass, $record, 'list');
            $handler = $this->actions->resolveRecordActionHandler('save');
        } catch (ActionRuntimeException $exception) {
            abort($exception->statusCode(), $exception->getMessage());
        }
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $this->actions->ensureRecordActionAuthorized($handler, $user, $listModelClass, $model);
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
        } catch (AuthorizationException $exception) {
            throw $exception;
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
