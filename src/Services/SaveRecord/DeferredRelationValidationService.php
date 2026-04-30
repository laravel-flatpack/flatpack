<?php

declare(strict_types=1);

namespace Flatpack\Services\SaveRecord;

use Flatpack\Schema\CompositionTabsMerge;
use Flatpack\Schema\Forms\FormFieldType;
use Flatpack\Support\ValidationMessages;

final class DeferredRelationValidationService
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $values
     * @return array<string, list<string>>
     */
    public function requiredRowErrors(?array $schema, array $values): array
    {
        if ($schema === null) {
            return [];
        }

        $schema = CompositionTabsMerge::form($schema) ?? $schema;

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
                        ValidationMessages::required($columnId),
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
}
