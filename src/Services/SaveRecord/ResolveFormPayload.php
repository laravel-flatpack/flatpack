<?php

declare(strict_types=1);

namespace Flatpack\Services\SaveRecord;

use Flatpack\Schema\Forms\FormFieldType;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

final class ResolveFormPayload
{
    /**
     * @return array<string, mixed>|null
     */
    public function resolve(Request $request): ?array
    {
        $values = $request->input('values');
        if (is_array($values)) {
            return $values;
        }

        $field = trim((string) $request->input('field', ''));
        if ($field === '') {
            return null;
        }

        return [$field => $request->input('value')];
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $values
     */
    public function validate(
        Model $model,
        string $compositionType,
        ?array $schema,
        array $values,
    ): WritablePayloadResult {
        $writableFields = $this->writableFieldsFromSchema($compositionType, $schema);
        $attributes = [];

        foreach ($values as $field => $value) {
            if (! is_string($field) || trim($field) === '') {
                continue;
            }
            if (! isset($writableFields[$field])) {
                continue;
            }

            $fieldDefinition = $this->fieldDefinitionForSchemaField($schema, $field);
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

            $attributes[$targetField] = $value;
        }

        return new WritablePayloadResult(
            attributes: $attributes,
            hasDeferredRelationPayload: $this->valuesHaveDeferredRelationFields($schema, $values),
        );
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
}
