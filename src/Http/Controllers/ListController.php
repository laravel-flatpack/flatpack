<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Actions\FlatpackBulkActionContext;
use Flatpack\Actions\Handlers\CreateRecordHandler;
use Flatpack\Actions\Handlers\DeleteRecordHandler;
use Flatpack\Actions\Handlers\EditRecordHandler;
use Flatpack\Actions\Handlers\SaveRecordHandler;
use Flatpack\Composition\EntityComposition;
use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Contracts\Actions\FlatpackBulkAction;
use Flatpack\Http\FlatpackResponse;
use Flatpack\Lists\ListBulkActions;
use Flatpack\Lists\ListHeaderActions;
use Flatpack\Lists\ListRecordsLoader;
use Flatpack\Support\ModelKeyResolver;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;
use Throwable;

final readonly class ListController
{
    public function __construct(
        private EntityComposition $entityComposition,
        private ListRecordsLoader $listRecords,
        private ModelKeyResolver $modelKeyResolver,
    ) {}

    public function index(Request $request, string $entity): Response|JsonResponse
    {
        $list = $this->entityComposition->listFor($entity);
        $schema = $this->entityComposition->listSchema($entity);

        $page = max(1, (int) $request->query('page', 1));
        $maxPerPage = (int) config('flatpack.list.max_per_page', 100);
        $perPage = (int) $request->query(
            'per_page',
            (int) config('flatpack.list.per_page', 10),
        );
        $perPage = max(1, min($maxPerPage, $perPage));
        $searchTerm = trim((string) $request->query('search', ''));
        $filters = $request->query('filters', []);
        $filters = is_array($filters) ? $filters : [];
        $sortBy = trim((string) $request->query('sort_by', ''));
        $sortDirection = mb_strtolower(trim((string) $request->query('sort_direction', '')));
        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $result = $this->listRecords->load(
            $list->model,
            $schema,
            $page,
            $perPage,
            $searchTerm,
            $filters,
            $sortBy,
            $sortDirection,
        );
        $flatpackPrefix = trim((string) config('flatpack.prefix', 'flatpack'), '/');

        return FlatpackResponse::inertia('list', [
            'entity' => $entity,
            'name' => $list->name,
            'model' => $list->model,
            'model_key' => $this->modelKeyResolver->resolve($list->model),
            'icon' => $list->icon,
            'order' => $list->order,
            'schema' => $schema,
            'records' => $result['records'],
            'pagination' => $result['pagination'],
            'search_term' => $searchTerm,
            'filters' => $result['filters'],
            'filter_values' => $result['filter_values'],
            'sorting' => $result['sorting'],
            'flatpack_prefix' => $flatpackPrefix,
            'list_actions' => ListHeaderActions::fromSchema($schema),
            'bulk_actions' => ListBulkActions::fromSchema($schema),
        ], $request->boolean('json'));
    }

    public function bulkAction(Request $request, string $entity): RedirectResponse
    {
        $list = $this->entityComposition->listFor($entity);
        $schema = $this->entityComposition->listSchema($entity);
        $action = trim((string) $request->input('action', ''));
        $handlerClass = config("flatpack.bulk_actions.{$action}");

        if (! is_string($handlerClass) || $handlerClass === '') {
            abort(404, 'Flatpack bulk action handler is not configured.');
        }

        $handler = app()->make($handlerClass);
        if (! $handler instanceof FlatpackBulkAction) {
            abort(500, 'Flatpack bulk action handler must implement FlatpackBulkAction.');
        }

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
        $handler = $this->resolveRecordActionHandler($action);
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
            throw $this->toUserFacingValidationException($exception);
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
        $model = $this->resolveRecordModel($listModelClass, $record);
        $handler = $this->resolveRecordActionHandler($action);
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
            throw $this->toUserFacingValidationException($exception);
        }

        if ($result instanceof RedirectResponse) {
            return $result->setStatusCode(303);
        }

        return back(303)->with('flatpack', [
            $action => true,
        ]);
    }

    public function updateRecord(
        Request $request,
        string $entity,
        string $record,
    ): RedirectResponse {
        [$listModelClass, $schema] = $this->listModelAndSchema($entity);
        $model = $this->resolveRecordModel($listModelClass, $record);
        $handler = $this->resolveRecordActionHandler('save');
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
            throw $this->toUserFacingValidationException($exception);
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

    private function resolveRecordActionHandler(string $action): FlatpackAction
    {
        $handlerClass = config("flatpack.actions.{$action}");
        if (! is_string($handlerClass) || $handlerClass === '') {
            $handlerClass = match ($action) {
                'create' => CreateRecordHandler::class,
                'edit' => EditRecordHandler::class,
                'save' => SaveRecordHandler::class,
                'delete' => DeleteRecordHandler::class,
                default => null,
            };
        }
        if (! is_string($handlerClass)) {
            abort(404, 'Flatpack action handler is not configured.');
        }

        $handler = app()->make($handlerClass);
        if (! $handler instanceof FlatpackAction) {
            abort(500, 'Flatpack action handler must implement FlatpackAction.');
        }

        return $handler;
    }

    private function resolveRecordModel(string $modelClass, string $record): Model
    {
        if ($modelClass === '' || ! class_exists($modelClass)) {
            abort(404, 'Flatpack list model is not configured.');
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            abort(404, 'Flatpack list model class is invalid.');
        }

        /** @var class-string<Model> $modelClass */
        $model = new $modelClass();
        $keyName = $model->getKeyName();

        return $modelClass::query()
            ->where($keyName, $record)
            ->firstOrFail();
    }

    private function toUserFacingValidationException(
        Throwable $exception,
    ): ValidationException {
        report($exception);

        $message = 'This change could not be completed.';
        if ($exception instanceof MassAssignmentException) {
            $message = config('app.debug')
                ? $exception->getMessage()
                : 'This field is not writable for this model.';
        } elseif (config('app.debug')) {
            $message = $exception->getMessage() !== ''
                ? $exception->getMessage()
                : $message;
        }

        return ValidationException::withMessages([
            'flatpack' => $message,
        ]);
    }
}
