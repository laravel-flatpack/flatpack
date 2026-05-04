<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Actions\ActionContext;
use Flatpack\Actions\BulkActionContext;
use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Facades\Flatpack;
use Flatpack\Http\Requests\BulkActionRequest;
use Flatpack\Http\Requests\ListActionRequest;
use Flatpack\Http\Response\FlatpackErrorPayload;
use Flatpack\Http\Response\RelationOptionsPayload;
use Flatpack\Schema\Forms\FormSchemaFields;
use Flatpack\Schema\Forms\FormSchemaNormalizer;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Flatpack\Services\Runtime\ActionRuntime;
use Flatpack\Support\EloquentModelResolver;
use Flatpack\Support\Exceptions\ActionRuntimeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class TableRowController
{
    public function __construct(
        private CompositionQuery $compositions,
        private WidgetSchemaNormalizer $widgetSchemaNormalizer,
        private FormSchemaNormalizer $formSchemaNormalizer,
        private ActionRuntime $actionRuntime,
    ) {}

    public function bulkDashboardWidgetRows(BulkActionRequest $request, string $widget): RedirectResponse
    {
        /** @var array<string, mixed>|null $schema */
        $schema = $this->compositions->optional(Flatpack::dashboardEntity(), 'list');
        $normalized = $this->widgetSchemaNormalizer->normalize($schema);
        $definition = $normalized['widgets'][$widget] ?? null;
        if (! is_array($definition) || ! self::isTableLikeWidgetType($definition['type'] ?? null)) {
            abort(404);
        }

        $modelClass = is_string($definition['model'] ?? null)
            ? trim((string) $definition['model'])
            : '';
        if ($modelClass === '' || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw ValidationException::withMessages([
                'flatpack' => 'Table is not model-backed.',
            ]);
        }
        $action = trim((string) $request->input('action', ''));
        if ($action === '') {
            abort(404, 'Flatpack bulk action is missing.');
        }

        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $handler = $this->actionRuntime->resolveBulkActionHandler($action);
        $this->actionRuntime->ensureBulkActionAuthorized($handler, $user, $modelClass);
        $result = $handler->handle(BulkActionContext::fromRequest(
            request: $request,
            user: $user,
            entity: Flatpack::dashboardEntity(),
            modelClass: $modelClass,
            schema: [
                'model' => $modelClass,
                'columns' => $definition['columns'] ?? [],
                'bulk_actions' => $definition['bulk_actions'] ?? [],
            ],
        ));

        return back(303)->with('flatpack', [
            $action => (int) $result,
        ]);
    }

    public function updateDashboardWidgetRow(Request $request, string $widget, string $record): RedirectResponse
    {
        /** @var array<string, mixed>|null $schema */
        $schema = $this->compositions->optional(Flatpack::dashboardEntity(), 'list');
        $normalized = $this->widgetSchemaNormalizer->normalize($schema);
        $definition = $normalized['widgets'][$widget] ?? null;
        if (! is_array($definition) || ! self::isTableLikeWidgetType($definition['type'] ?? null)) {
            abort(404);
        }

        return $this->updateModelRow(
            request: $request,
            entity: Flatpack::dashboardEntity(),
            record: $record,
            modelValue: $definition['model'] ?? null,
            columnsValue: $definition['columns'] ?? null,
        );
    }

    public function rowActionDashboardWidgetRow(
        ListActionRequest $request,
        string $widget,
        string $record,
    ): RedirectResponse {
        /** @var array<string, mixed>|null $schema */
        $schema = $this->compositions->optional(Flatpack::dashboardEntity(), 'list');
        $normalized = $this->widgetSchemaNormalizer->normalize($schema);
        $definition = $normalized['widgets'][$widget] ?? null;
        if (! is_array($definition) || ! self::isTableLikeWidgetType($definition['type'] ?? null)) {
            abort(404);
        }

        $modelClass = is_string($definition['model'] ?? null)
            ? trim((string) $definition['model'])
            : '';
        if ($modelClass === '' || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw ValidationException::withMessages([
                'flatpack' => 'Table is not model-backed.',
            ]);
        }

        $action = trim((string) $request->input('action', ''));
        if ($action === '') {
            abort(404, 'Flatpack row action is missing.');
        }

        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $handler = $this->actionRuntime->resolveRecordActionHandler($action);
        $model = $this->actionRuntime->resolveRecordModel(
            $modelClass,
            $record,
            'table row',
        );
        $this->actionRuntime->ensureRecordActionAuthorized(
            $handler,
            $user,
            $modelClass,
            $model,
        );

        try {
            $handler->handle(new ActionContext(
                request: $request,
                entity: Flatpack::dashboardEntity(),
                actionName: $action,
                modelClass: $modelClass,
                record: $record,
                compositionType: 'list',
                schema: [
                    'model' => $modelClass,
                    'columns' => $definition['columns'] ?? [],
                ],
                model: $model,
            ));
        } catch (Throwable $exception) {
            report($exception);
            throw $this->actionRuntime->toUserFacingValidationException($exception);
        }

        return back(303)->with('flatpack', [
            $action => true,
        ]);
    }

    public function dashboardWidgetRelationOptions(Request $request, string $widget): JsonResponse
    {
        /** @var array<string, mixed>|null $schema */
        $schema = $this->compositions->optional(Flatpack::dashboardEntity(), 'list');
        $normalized = $this->widgetSchemaNormalizer->normalize($schema);
        $definition = $normalized['widgets'][$widget] ?? null;
        if (! is_array($definition) || ! self::isTableLikeWidgetType($definition['type'] ?? null)) {
            return FlatpackErrorPayload::notFound('Flatpack dashboard widget is not configured.');
        }

        $modelClass = is_string($definition['model'] ?? null)
            ? trim((string) $definition['model'])
            : '';
        if ($modelClass === '' || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return FlatpackErrorPayload::notFound('Flatpack dashboard widget is not model-backed.');
        }

        $validated = $request->validate([
            'column_id' => ['required', 'string'],
        ]);
        $columnId = trim((string) $validated['column_id']);
        $columns = $definition['columns'] ?? null;
        if (! is_array($columns) || $columns === []) {
            return FlatpackErrorPayload::notFound('Flatpack dashboard widget columns are not configured.');
        }

        $column = null;
        if (array_is_list($columns)) {
            foreach ($columns as $item) {
                if (! is_array($item)) {
                    continue;
                }
                if (trim((string) ($item['id'] ?? '')) === $columnId) {
                    $column = $item;
                    break;
                }
            }
        } else {
            $candidate = $columns[$columnId] ?? null;
            if (is_array($candidate)) {
                $column = $candidate;
            }
        }

        if (! is_array($column) || trim((string) ($column['type'] ?? '')) !== 'relation') {
            return FlatpackErrorPayload::notFound('Flatpack dashboard widget relation column is not configured.');
        }

        $fieldDefinition = $column;
        $fieldDefinition['type'] = 'combobox';

        return response()->json(RelationOptionsPayload::forModelField(
            $modelClass,
            $fieldDefinition,
            $request,
        ));
    }

    public function updateFormTableRow(Request $request, string $entity, string $field, string $record): RedirectResponse
    {
        /** @var array<string, mixed>|null $rawSchema */
        $rawSchema = $this->compositions->optional($entity, 'form');
        $formModelClass = is_array($rawSchema) ? trim((string) ($rawSchema['model'] ?? '')) : '';
        $schema = $this->formSchemaNormalizer->normalizedFormSchema(
            $rawSchema,
            formModelClass: $formModelClass !== '' ? $formModelClass : null,
        );
        $definition = FormSchemaFields::fieldDefinitionById($schema?->toArray(), $field);
        if (! is_array($definition) || (($definition['type'] ?? null) !== 'table')) {
            abort(404);
        }
        // Note: form-table updates remain `table`-only — embedded form table fields use `type: table` and
        // there is no embedded grid form field. Grid is a dashboard widget renderer only.
        if (isset($definition['relation']) && trim((string) $definition['relation']) !== '') {
            throw ValidationException::withMessages([
                'flatpack' => 'Immediate row updates are not supported for relation-backed tables.',
            ]);
        }

        return $this->updateModelRow(
            request: $request,
            entity: $entity,
            record: $record,
            modelValue: $definition['model'] ?? null,
            columnsValue: $definition['columns'] ?? null,
        );
    }

    /**
     * Matches embedded / dashboard table draft rows ({@code __new__:…}) from the row drawer toolbar.
     */
    private static function isEmbeddedTableDraftRecordId(string $record): bool
    {
        return str_starts_with(trim($record), '__new__:');
    }

    /**
     * Dashboard widgets that share the table row endpoints (table and grid). Grid is a renderer
     * variant and uses the same model-backed pipeline, including row updates and bulk actions.
     */
    private static function isTableLikeWidgetType(mixed $type): bool
    {
        return $type === 'table' || $type === 'grid';
    }

    private function updateModelRow(
        Request $request,
        string $entity,
        string $record,
        mixed $modelValue,
        mixed $columnsValue,
    ): RedirectResponse {
        $modelClass = is_string($modelValue) ? trim($modelValue) : '';
        if ($modelClass === '' || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw ValidationException::withMessages([
                'flatpack' => 'Table is not model-backed.',
            ]);
        }

        $editableColumns = $this->editableColumnIds($columnsValue);
        if ($editableColumns === []) {
            throw ValidationException::withMessages([
                'flatpack' => 'No editable columns configured for this table.',
            ]);
        }

        $request->validate([
            'values' => ['required', 'array'],
        ]);
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }
        try {
            $handler = $this->actionRuntime->resolveRecordActionHandler('save');
            if (self::isEmbeddedTableDraftRecordId($record)) {
                $prototype = EloquentModelResolver::fromClass($modelClass);
                if ($prototype === null) {
                    throw new ActionRuntimeException(404, 'Flatpack table row model is not configured.');
                }
                $model = new $modelClass();
            } else {
                $model = $this->actionRuntime->resolveRecordModel($modelClass, $record, 'table row');
            }
        } catch (ActionRuntimeException $exception) {
            abort($exception->statusCode(), $exception->getMessage());
        }
        $this->actionRuntime->ensureRecordActionAuthorized($handler, $user, $modelClass, $model);
        $schema = [
            'model' => $modelClass,
            'columns' => $columnsValue,
        ];
        try {
            $handler->handle(new ActionContext(
                request: $request,
                entity: $entity,
                actionName: 'save',
                modelClass: $modelClass,
                record: $record,
                compositionType: 'list',
                schema: $schema,
                model: $model,
            ));
        } catch (Throwable $exception) {
            report($exception);
            throw $this->actionRuntime->toUserFacingValidationException($exception);
        }

        return back(303);
    }

    /**
     * @return list<string>
     */
    private function editableColumnIds(mixed $columnsValue): array
    {
        if (! is_array($columnsValue)) {
            return [];
        }
        $editable = [];
        if (array_is_list($columnsValue)) {
            foreach ($columnsValue as $column) {
                if (! is_array($column)) {
                    continue;
                }
                $id = trim((string) ($column['id'] ?? ''));
                if ($id === '' || ($column['editable'] ?? false) !== true) {
                    continue;
                }
                $editable[] = $id;
            }

            return $editable;
        }

        foreach ($columnsValue as $columnId => $column) {
            if (! is_array($column)) {
                continue;
            }
            $id = trim((string) ($column['id'] ?? (is_string($columnId) ? $columnId : '')));
            if ($id === '' || ($column['editable'] ?? false) !== true) {
                continue;
            }
            $editable[] = $id;
        }

        return $editable;
    }
}
