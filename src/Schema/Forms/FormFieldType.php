<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms;

/**
 * Canonical YAML form field types used across schema normalization and validation.
 */
final class FormFieldType
{
    /**
     * Maps YAML aliases to the canonical type string used by the UI and validators.
     */
    public static function normalizeYamlType(string $type): string
    {
        $type = trim($type);

        return match ($type) {
            'date' => 'date-picker',
            default => $type,
        };
    }

    /**
     * Relation-backed field: {@code type: relation} or remote combobox with {@code relation} set.
     *
     * @param  array<string, mixed>  $fieldDefinition
     */
    public static function isRelationField(array $fieldDefinition): bool
    {
        $type = self::normalizeYamlType(trim((string) ($fieldDefinition['type'] ?? '')));

        return $type === 'relation'
            || ($type === 'combobox' && isset($fieldDefinition['relation']));
    }
}
