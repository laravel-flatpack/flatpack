<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Actions\RelationFormSynchronizer;
use Flatpack\Schema\Forms\FormFieldType;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

final class SaveRecordHandler extends FlatpackActionHandler
{
    public function __construct(
        private readonly RelationFormSynchronizer $relationFormSynchronizer,
    ) {}

    public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
    {
        if ($this->modelExists($model)) {
            return $this->canPerformAction(
                user: $user,
                ability: 'update',
                modelClass: $modelClass,
                model: $model,
            );
        }

        return $this->canPerformAction(
            user: $user,
            ability: 'create',
            modelClass: $modelClass,
            model: $model,
        );
    }

    public function handle(FlatpackActionContext $context): mixed
    {
        $model = $this->resolveModel($context);
        if (! $model instanceof Model) {
            return null;
        }

        $values = $context->request->input('values');
        if (! is_array($values)) {
            $field = trim((string) $context->request->input('field', ''));
            if ($field === '') {
                return $model;
            }
            $values = [$field => $context->request->input('value')];
        }

        $writableFields = $this->writableFieldsFromSchema(
            compositionType: $context->compositionType,
            schema: $context->schema,
        );
        $filtered = [];

        foreach ($values as $field => $value) {
            if (! is_string($field) || trim($field) === '') {
                continue;
            }
            if (! isset($writableFields[$field])) {
                continue;
            }

            $fieldDefinition = $this->fieldDefinitionForSchemaField($context->schema, $field);
            if ($fieldDefinition !== null && FormFieldType::shouldDeferToRelationSync($fieldDefinition)) {
                continue;
            }

            $targetField = $field;
            if (
                $fieldDefinition !== null
                && FormFieldType::isSingleRelationCombobox($fieldDefinition)
                && ! $model->isFillable($field)
            ) {
                $mapped = $this->massAssignableFieldForSingleRelationCombobox(
                    $model,
                    $fieldDefinition,
                    $field,
                );
                if ($mapped !== null) {
                    $targetField = $mapped;
                }
            }

            if (! $model->isFillable($targetField)) {
                throw new MassAssignmentException(sprintf(
                    'Add [%s] to fillable property to allow mass assignment on [%s].',
                    $targetField,
                    $model::class,
                ));
            }
            $filtered[$targetField] = $value;
        }

        $hasDeferredRelationPayload = $this->valuesHaveDeferredRelationFields($context->schema, $values);

        if ($filtered === [] && ! $hasDeferredRelationPayload) {
            return $model;
        }

        if ($filtered !== []) {
            $model->fill($filtered);
            $model->save();
        }

        $saved = $filtered !== [] ? ($model->fresh() ?? $model) : $model;

        if (
            $context->compositionType === 'form'
            && $saved instanceof Model
            && $saved->getKey() !== null
        ) {
            $rowValidationErrors = $this->deferredRelationRequiredErrors($context->schema, $values);
            if ($rowValidationErrors !== []) {
                throw ValidationException::withMessages($rowValidationErrors);
            }
            try {
                $this->relationFormSynchronizer->sync($saved, $context->schema, $values);
            } catch (QueryException $exception) {
                $mapped = $this->relationSyncValidationError($exception, $context->schema, $values);
                if ($mapped !== null) {
                    throw ValidationException::withMessages([
                        $mapped['field'] => $mapped['message'],
                    ]);
                }

                throw $exception;
            }
        }

        return $saved instanceof Model ? ($saved->fresh() ?? $saved) : $saved;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $values
     */
    private function valuesHaveDeferredRelationFields(?array $schema, array $values): bool
    {
        if ($schema === null) {
            return false;
        }

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return false;
        }

        foreach ($fields as $yamlKey => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }

            $id = trim((string) ($fieldDefinition['id'] ?? $yamlKey));
            if ($id === '' || ! FormFieldType::shouldDeferToRelationSync($fieldDefinition)) {
                continue;
            }

            if (array_key_exists($id, $values)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    private function fieldDefinitionForSchemaField(?array $schema, string $fieldId): ?array
    {
        if ($schema === null) {
            return null;
        }

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return null;
        }

        foreach ($fields as $yamlKey => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }

            $id = trim((string) ($fieldDefinition['id'] ?? $yamlKey));
            if ($id === $fieldId) {
                return $fieldDefinition;
            }
        }

        return null;
    }

    private function resolveModel(FlatpackActionContext $context): ?Model
    {
        if ($context->model instanceof Model) {
            return $context->model;
        }

        $modelClass = trim($context->modelClass);
        if ($modelClass === '' || ! class_exists($modelClass)) {
            return null;
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        return new $modelClass();
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function massAssignableFieldForSingleRelationCombobox(
        Model $model,
        array $fieldDefinition,
        string $fallback,
    ): ?string {
        $relationName = isset($fieldDefinition['relation'])
            ? trim((string) $fieldDefinition['relation'])
            : '';
        if ($relationName === '' || ! method_exists($model, $relationName)) {
            return null;
        }

        $relation = $model->{$relationName}();
        if (! $relation instanceof BelongsTo) {
            return null;
        }

        $foreignKey = trim($relation->getForeignKeyName());
        if ($foreignKey === '' || $foreignKey === $fallback) {
            return null;
        }

        return $foreignKey;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, true>
     */
    private function writableFieldsFromSchema(
        string $compositionType,
        ?array $schema,
    ): array {
        if ($schema === null) {
            return [];
        }

        return match ($compositionType) {
            'form' => $this->formFieldsFromSchema($schema),
            'list' => $this->editableListColumnsFromSchema($schema),
            default => $this->editableListColumnsFromSchema($schema),
        };
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, true>
     */
    private function editableListColumnsFromSchema(array $schema): array
    {
        $columns = $schema['columns'] ?? null;
        if (! is_array($columns)) {
            return [];
        }

        $editable = [];
        foreach ($columns as $columnId => $columnDefinition) {
            if (! is_array($columnDefinition)) {
                continue;
            }
            if (($columnDefinition['editable'] ?? false) !== true) {
                continue;
            }
            $id = trim((string) ($columnDefinition['id'] ?? $columnId));
            if ($id === '') {
                continue;
            }
            $editable[$id] = true;
        }

        return $editable;
    }

    /**
     * Form schemas are expected to describe writable fields directly under
     * `fields`, so the presence of a field definition opts it into the save
     * action. This keeps the handler ready for future form submissions while
     * the model `fillable` contract remains the final write guard.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, true>
     */
    private function formFieldsFromSchema(array $schema): array
    {
        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return [];
        }

        $writable = [];
        foreach ($fields as $fieldId => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }

            $id = trim((string) ($fieldDefinition['id'] ?? $fieldId));
            if ($id === '') {
                continue;
            }

            $writable[$id] = true;
        }

        return $writable;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $values
     * @return array<string, list<string>>
     */
    private function deferredRelationRequiredErrors(?array $schema, array $values): array
    {
        if ($schema === null) {
            return [];
        }
        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return [];
        }

        $errors = [];
        foreach ($fields as $yamlKey => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }
            if (! FormFieldType::shouldDeferToRelationSync($fieldDefinition)) {
                continue;
            }

            $fieldId = trim((string) ($fieldDefinition['id'] ?? $yamlKey));
            if ($fieldId === '' || ! array_key_exists($fieldId, $values)) {
                continue;
            }
            $rows = $values[$fieldId];
            if (! is_array($rows)) {
                continue;
            }

            $requiredColumns = $this->requiredTableColumns($fieldDefinition);
            if ($requiredColumns === []) {
                continue;
            }

            foreach (array_values($rows) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                foreach ($requiredColumns as $columnId) {
                    $raw = $row[$columnId] ?? null;
                    if ($raw !== null && trim((string) $raw) !== '') {
                        continue;
                    }
                    $errors[sprintf('values.%s.%d.%s', $fieldId, $index, $columnId)] = [
                        $this->defaultRequiredMessage($columnId),
                    ];
                }
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return list<string>
     */
    private function requiredTableColumns(array $fieldDefinition): array
    {
        $columns = $fieldDefinition['columns'] ?? null;
        if (! is_array($columns)) {
            return [];
        }

        $required = [];
        if (array_is_list($columns)) {
            foreach ($columns as $columnDefinition) {
                if (! is_array($columnDefinition)) {
                    continue;
                }
                $columnId = trim((string) ($columnDefinition['id'] ?? ''));
                if ($columnId === '') {
                    continue;
                }
                $editField = $columnDefinition['edit_form_field'] ?? null;
                $isRequired = (is_array($editField) && (($editField['required'] ?? false) === true))
                    || (($columnDefinition['required'] ?? false) === true);
                if ($isRequired) {
                    $required[] = $columnId;
                }
            }

            return $required;
        }

        foreach ($columns as $columnId => $columnDefinition) {
            if (! is_array($columnDefinition)) {
                continue;
            }
            $id = trim((string) $columnId);
            if ($id === '') {
                continue;
            }
            $editField = $columnDefinition['edit_form_field'] ?? null;
            $isRequired = (is_array($editField) && (($editField['required'] ?? false) === true))
                || (($columnDefinition['required'] ?? false) === true);
            if ($isRequired) {
                $required[] = $id;
            }
        }

        return $required;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $values
     * @return array{field: string, message: string}|null
     */
    private function relationSyncValidationError(
        QueryException $exception,
        ?array $schema,
        array $values,
    ): ?array {
        $column = $this->requiredColumnFromQueryException($exception);
        if ($column === null || $schema === null) {
            return null;
        }

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return null;
        }

        foreach ($fields as $yamlKey => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }
            if (! FormFieldType::shouldDeferToRelationSync($fieldDefinition)) {
                continue;
            }

            $fieldId = trim((string) ($fieldDefinition['id'] ?? $yamlKey));
            if ($fieldId === '' || ! array_key_exists($fieldId, $values)) {
                continue;
            }

            $rows = $values[$fieldId];
            if (! is_array($rows)) {
                continue;
            }

            if (! $this->hasTableColumn($fieldDefinition, $column)) {
                continue;
            }

            foreach (array_values($rows) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $raw = $row[$column] ?? null;
                if ($raw !== null && trim((string) $raw) !== '') {
                    continue;
                }

                return [
                    'field' => sprintf('values.%s.%d.%s', $fieldId, $index, $column),
                    'message' => $this->defaultRequiredMessage($column),
                ];
            }
        }

        return null;
    }

    private function requiredColumnFromQueryException(QueryException $exception): ?string
    {
        $message = $exception->getMessage();

        if (
            preg_match(
                '/NOT NULL constraint failed: [^.]+\.([a-zA-Z0-9_]+)/',
                $message,
                $matches,
            ) === 1
        ) {
            return $matches[1];
        }

        return null;
    }

    private function defaultRequiredMessage(string $field): string
    {
        return sprintf('%s is required.', str_replace('_', ' ', ucfirst($field)));
    }

    /**
     * @param  array<string, mixed>  $columnDefinition
     */
    private function hasTableColumn(array $fieldDefinition, string $columnId): bool
    {
        $columns = $fieldDefinition['columns'] ?? null;
        if (! is_array($columns)) {
            return false;
        }
        if (array_is_list($columns)) {
            foreach ($columns as $columnDefinition) {
                if (! is_array($columnDefinition)) {
                    continue;
                }
                $id = trim((string) ($columnDefinition['id'] ?? ''));
                if ($id === $columnId) {
                    return true;
                }
            }

            return false;
        }

        return array_key_exists($columnId, $columns);
    }
}
