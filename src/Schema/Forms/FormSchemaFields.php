<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms;

/**
 * Helpers for reading {@code fields} blocks in form (or similar) YAML schema arrays.
 */
final class FormSchemaFields
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    public static function fieldDefinitionById(?array $schema, string $fieldId): ?array
    {
        if ($schema === null) {
            return null;
        }

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return null;
        }

        foreach ($fields as $key => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }

            $id = trim((string) ($fieldDefinition['id'] ?? $key));
            if ($id !== $fieldId) {
                continue;
            }

            return $fieldDefinition;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $tableFieldDefinition
     *                                                      A `type: table` field definition with a {@code columns} block.
     * @return array<string, mixed>|null
     */
    public static function embeddedTableColumnById(
        array $tableFieldDefinition,
        string $columnId,
    ): ?array {
        $columns = $tableFieldDefinition['columns'] ?? null;
        if (! is_array($columns)) {
            return null;
        }

        $columnId = trim($columnId);
        if ($columnId === '') {
            return null;
        }

        if (array_is_list($columns)) {
            foreach ($columns as $c) {
                if (! is_array($c)) {
                    continue;
                }
                $id = trim((string) ($c['id'] ?? ''));
                if ($id === $columnId) {
                    return $c;
                }
            }

            return null;
        }

        foreach ($columns as $key => $c) {
            if (! is_array($c)) {
                continue;
            }
            $id = trim((string) ($c['id'] ?? (is_string($key) ? $key : '')));
            if ($id === $columnId) {
                return $c;
            }
        }

        return null;
    }
}
