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
}
