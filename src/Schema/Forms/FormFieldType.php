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
     * Single-value relation picker: {@code type: combobox} with non-empty {@code relation} (remote options API + exists rules).
     * Not for {@code type: table} — use {@see isRelationBackedTable()} / {@see shouldDeferToRelationSync()}.
     *
     * @param  array<string, mixed>  $fieldDefinition
     */
    public static function isRelationBackedCombobox(array $fieldDefinition): bool
    {
        $type = self::normalizeYamlType(trim((string) ($fieldDefinition['type'] ?? '')));

        if ($type !== 'combobox') {
            return false;
        }

        $relation = isset($fieldDefinition['relation'])
            ? trim((string) $fieldDefinition['relation'])
            : '';

        return $relation !== '';
    }

    /**
     * Values for these fields are persisted via relation sync, not mass assignment on the parent.
     *
     * @param  array<string, mixed>  $fieldDefinition
     */
    public static function shouldDeferToRelationSync(array $fieldDefinition): bool
    {
        $type = self::normalizeYamlType(trim((string) ($fieldDefinition['type'] ?? '')));

        $relation = isset($fieldDefinition['relation'])
            ? trim((string) $fieldDefinition['relation'])
            : '';

        if ($relation === '') {
            return false;
        }

        if ($type === 'table') {
            return true;
        }

        if ($type === 'combobox' && ($fieldDefinition['multiple'] ?? false) === true) {
            return true;
        }

        return false;
    }

    /**
     * Relation-backed inline table (`type: table` + non-empty {@code relation}).
     *
     * @param  array<string, mixed>  $fieldDefinition
     */
    public static function isRelationBackedTable(array $fieldDefinition): bool
    {
        $type = self::normalizeYamlType(trim((string) ($fieldDefinition['type'] ?? '')));

        if ($type !== 'table') {
            return false;
        }

        $relation = isset($fieldDefinition['relation'])
            ? trim((string) $fieldDefinition['relation'])
            : '';

        return $relation !== '';
    }
}
