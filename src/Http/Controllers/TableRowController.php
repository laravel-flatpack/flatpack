<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Actions\FlatpackBulkActionContext;
use Flatpack\Facades\Flatpack;
use Flatpack\Http\Requests\BulkActionRequest;
use Flatpack\Schema\Forms\FormSchemaFields;
use Flatpack\Schema\Forms\FormSchemaNormalizer;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Flatpack\Services\Runtime\ActionRuntime;
use Flatpack\Support\Exceptions\ActionRuntimeException;
use Illuminate\Database\Eloquent\Model;
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
        if (! is_array($definition) || (($definition['type'] ?? null) !== 'table')) {
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
        $result = $handler->handle(FlatpackBulkActionContext::fromRequest(
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
        if (! is_array($definition) || (($definition['type'] ?? null) !== 'table')) {
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

    public function updateFormTableRow(Request $request, string $entity, string $field, string $record): RedirectResponse
    {
        /** @var array<string, mixed>|null $rawSchema */
        $rawSchema = $this->compositions->optional($entity, 'form');
        $formModelClass = is_array($rawSchema) ? trim((string) ($rawSchema['model'] ?? '')) : '';
        $schema = $this->formSchemaNormalizer->normalizedFormSchema(
            $rawSchema,
            formModelClass: $formModelClass !== '' ? $formModelClass : null,
        );
        $definition = FormSchemaFields::fieldDefinitionById($schema, $field);
        if (! is_array($definition) || (($definition['type'] ?? null) !== 'table')) {
            abort(404);
        }
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

    private function updateModelRow(
        Request $request,
        string $entity,
        string $record,
        mixed $modelValue,
        mixed $columnsValue,
    ): RedirectResponse
    {
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
            $model = $this->actionRuntime->resolveRecordModel($modelClass, $record, 'table row');
        } catch (ActionRuntimeException $exception) {
            abort($exception->statusCode(), $exception->getMessage());
        }
        $this->actionRuntime->ensureRecordActionAuthorized($handler, $user, $modelClass, $model);
        $schema = [
            'model' => $modelClass,
            'columns' => $columnsValue,
        ];
        try {
            $handler->handle(new FlatpackActionContext(
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
