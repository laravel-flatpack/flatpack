<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Actions\BulkActionContext;
use Flatpack\Http\Controllers\Concerns\DispatchesActions;
use Flatpack\Http\Controllers\Concerns\HandlesReorderRecord;
use Flatpack\Http\Controllers\Concerns\LoadsListComposition;
use Flatpack\Http\Requests\BulkActionRequest;
use Flatpack\Http\Requests\ListActionRequest;
use Flatpack\Http\Requests\ListRecordUpdateRequest;
use Flatpack\Http\Requests\ReorderRequest;
use Flatpack\Services\Lists\ActiveTabResolver;
use Flatpack\Support\SuccessRedirect;
use Flatpack\Support\SuccessRedirectSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final readonly class EntityActionController
{
    use DispatchesActions;
    use HandlesReorderRecord;
    use LoadsListComposition;

    public function __construct(
        private ActiveTabResolver $activeTabResolver,
    ) {}

    public function bulkAction(BulkActionRequest $request, string $entity): RedirectResponse
    {
        $list = $this->loadList($entity);
        $schema = $this->loadListSchema($entity);
        $listModelClass = $this->listModelClass($list);
        $action = trim((string) $request->input('action', ''));
        if ($action === '') {
            abort(404, 'Flatpack bulk action is missing.');
        }

        $user = $this->requireUserOrAbort($request);
        $handler = $this->resolveBulkActionHandlerOrAbort($action);
        $this->actionRuntime()->ensureBulkActionAuthorized($handler, $user, $listModelClass);

        $tab = trim((string) $request->input('tab', ''));
        $resolvedTab = $this->activeTabResolver->resolveWithSchema($schema, $tab);

        $result = $this->executeBulkAction(
            $handler,
            BulkActionContext::fromRequest(
                request: $request,
                user: $user,
                entity: $entity,
                modelClass: $listModelClass,
                schema: $schema,
                scope: $resolvedTab->scope ?? '',
            ),
        );

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

        $user = $this->requireUserOrAbort($request);

        [$listModelClass, $schema] = $this->listModelAndSchema($entity);
        $handler = $this->resolveRecordActionHandlerOrAbort($action);
        $this->actionRuntime()->ensureRecordActionAuthorized($handler, $user, $listModelClass, null);
        $result = $this->executeRecordActionContext(
            handler: $handler,
            request: $request,
            entity: $entity,
            actionName: $action,
            modelClass: $listModelClass,
            record: null,
            compositionType: 'list',
            schema: $schema,
            model: null,
        );

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

        $user = $this->requireUserOrAbort($request);

        [$listModelClass, $schema] = $this->listModelAndSchema($entity);
        $model = $this->resolveListRecordModelOrAbort(
            $listModelClass,
            $record,
            in_array($action, ['restore', 'force_delete'], true),
        );
        $handler = $this->resolveRecordActionHandlerOrAbort($action);
        $this->actionRuntime()->ensureRecordActionAuthorized($handler, $user, $listModelClass, $model);
        $result = $this->executeRecordActionContext(
            handler: $handler,
            request: $request,
            entity: $entity,
            actionName: $action,
            modelClass: $listModelClass,
            record: $record,
            compositionType: 'list',
            schema: $schema,
            model: $model,
        );

        if ($result instanceof RedirectResponse) {
            return $result->setStatusCode(303);
        }

        $formSchema = $this->entityComposition()->formSchema($entity);
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
        $model = $this->resolveListRecordModelOrAbort($listModelClass, $record);
        $handler = $this->resolveRecordActionHandlerOrAbort('save');
        $user = $this->requireUserOrAbort($request);
        $this->actionRuntime()->ensureRecordActionAuthorized($handler, $user, $listModelClass, $model);
        $this->executeRecordActionContext(
            handler: $handler,
            request: $request,
            entity: $entity,
            actionName: 'save',
            modelClass: $listModelClass,
            record: $record,
            compositionType: 'list',
            schema: $schema,
            model: $model,
        );

        return back(303)->with('flatpack', [
            'save' => true,
        ]);
    }

    public function reorderRecord(
        ReorderRequest $request,
        string $entity,
        string $record,
    ): JsonResponse {
        [$fallbackModelClass, $schema] = $this->listModelAndSchema($entity);
        $requestedTabId = trim((string) $request->query('tab', ''));
        $resolvedTab = $this->activeTabResolver->resolveWithSchema($schema, $requestedTabId);
        $resolved = $this->resolveReorderSchemaAndModelClass(
            schema: $resolvedTab->effectiveSchema?->toArray(),
            fallbackModelClass: $fallbackModelClass,
        );
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }
        $schema = $resolved['schema'];
        $modelClass = $resolved['modelClass'];
        $column = $this->resolveReorderColumn($schema);
        if ($column === null) {
            return response()->json(['message' => 'Reordering is not enabled for this model.'], 422);
        }

        $model = $this->resolveReorderTargetModelOrJson404($modelClass, $record);
        if ($model instanceof JsonResponse) {
            return $model;
        }

        $handler = $this->resolveReorderHandlerOrJsonError();
        if ($handler instanceof JsonResponse) {
            return $handler;
        }

        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        $this->actionRuntime()->ensureRecordActionAuthorized($handler, $user, $modelClass, $model);

        $reordered = $this->executeReorderOrJsonError(
            request: $request,
            handler: $handler,
            entity: $entity,
            record: $record,
            modelClass: $modelClass,
            schema: $schema,
            model: $model,
            scope: $resolvedTab->scope,
        );
        if ($reordered instanceof JsonResponse) {
            return $reordered;
        }

        return response()->json([
            'id' => $reordered->getKey(),
            $column => $reordered->getAttribute($column),
        ]);
    }

    /**
     * @return array{string, array<string, mixed>|null}
     */
    private function listModelAndSchema(string $entity): array
    {
        $list = $this->loadList($entity);
        $schema = $this->loadListSchema($entity);

        return [$this->listModelClass($list), $schema];
    }
}
