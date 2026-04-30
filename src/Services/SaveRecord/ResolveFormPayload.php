<?php

declare(strict_types=1);

namespace Flatpack\Services\SaveRecord;

use Flatpack\Schema\CompositionTabsMerge;
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
        $schema = $this->normalizedSchema($compositionType, $schema);
        $attributes = $this->writableAttributes($model, $compositionType, $schema, $values);

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
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function writableAttributes(
        Model $model,
        string $compositionType,
        ?array $schema,
        array $values,
    ): array {
        $writableFields = $this->writableFieldsFromSchema($compositionType, $schema);
        $attributes = [];

        foreach ($values as $field => $value) {
            if (! is_string($field) || trim($field) === '' || ! isset($writableFields[$field])) {
                continue;
            }

            $fieldDefinition = $this->fieldDefinitionForSchemaField($schema, $field);
            if ($fieldDefinition !== null && FormFieldType::shouldDeferToRelationSync($fieldDefinition)) {
                continue;
            }

            if ($fieldDefinition !== null && $this->isFileUploadField($fieldDefinition)) {
                $targetField = $this->fileUploadTargetColumn($fieldDefinition, $field);
                $this->assertMassAssignable($model, $targetField);
                $attributes[$targetField] = $this->normalizedFileUploadValue($fieldDefinition, $value);

                continue;
            }

            $targetField = $field;
            if (
                $fieldDefinition !== null
                && FormFieldType::isSingleRelationCombobox($fieldDefinition)
                && ! $model->isFillable($field)
            ) {
                $targetField = $this->belongsToForeignKey($model, $fieldDefinition, $field) ?? $field;
            }

            $this->assertMassAssignable($model, $targetField);
            $attributes[$targetField] = $value;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    private function normalizedSchema(string $compositionType, ?array $schema): ?array
    {
        if ($schema === null) {
            return null;
        }

        return match ($compositionType) {
            'form' => CompositionTabsMerge::form($schema) ?? $schema,
            'list' => CompositionTabsMerge::list($schema) ?? $schema,
            default => $schema,
        };
    }

    private function assertMassAssignable(Model $model, string $targetField): void
    {
        if ($model->isFillable($targetField)) {
            return;
        }

        throw new MassAssignmentException(sprintf(
            'Add [%s] to fillable property to allow mass assignment on [%s].',
            $targetField,
            $model::class,
        ));
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
    private function belongsToForeignKey(
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

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function isFileUploadField(array $fieldDefinition): bool
    {
        return FormFieldType::normalizeYamlType(trim((string) ($fieldDefinition['type'] ?? ''))) === 'file-upload'
            && trim((string) ($fieldDefinition['mode'] ?? 'url')) === 'url';
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function fileUploadTargetColumn(array $fieldDefinition, string $fallback): string
    {
        $target = trim((string) ($fieldDefinition['target_column'] ?? ''));

        return $target !== '' ? $target : $fallback;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function normalizedFileUploadValue(array $fieldDefinition, mixed $value): mixed
    {
        $multiple = ($fieldDefinition['multiple'] ?? false) === true;
        if (! is_array($value)) {
            return $multiple ? [] : null;
        }

        $persistAs = trim((string) ($fieldDefinition['persist_as'] ?? 'string'));
        $items = array_is_list($value) ? $value : [$value];
        $urls = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $url = trim((string) ($item['url'] ?? $item['path'] ?? ''));
            if ($url !== '') {
                $urls[] = $url;
            }
        }

        if ($multiple) {
            return $persistAs === 'json' ? $urls : implode(',', $urls);
        }

        return $urls[0] ?? null;
    }
}
