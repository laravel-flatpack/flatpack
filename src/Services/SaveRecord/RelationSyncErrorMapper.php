<?php

declare(strict_types=1);

namespace Flatpack\Services\SaveRecord;

use Flatpack\Schema\Forms\FormCompositionMergeForPersistence;
use Flatpack\Schema\Forms\FormFieldType;
use Flatpack\Support\DatabaseConstraintParser;
use Flatpack\Support\ValidationMessages;
use Illuminate\Database\QueryException;

final class RelationSyncErrorMapper
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $values
     * @return array{field: string, message: string}|null
     */
    public function mapRequiredConstraint(
        QueryException $exception,
        ?array $schema,
        array $values,
    ): ?array {
        $column = $this->requiredColumnFromQueryException($exception);
        if ($column === null || $schema === null) {
            return null;
        }

        $schema = FormCompositionMergeForPersistence::merge($schema) ?? $schema;

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
                    'message' => ValidationMessages::required($column),
                ];
            }
        }

        return null;
    }

    private function requiredColumnFromQueryException(QueryException $exception): ?string
    {
        return DatabaseConstraintParser::notNullColumn($exception);
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
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
