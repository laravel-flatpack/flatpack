<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms;

/**
 * Canonical YAML form field types used across schema normalization and validation.
 */
final class FormFieldType
{
    /**
     * Any form field backed by an Eloquent relation (`relation` + supported type).
     *
     * @param  array<string, mixed>  $fieldDefinition
     */
    public static function isRelationBackedField(array $fieldDefinition): bool
    {
        return self::relationName($fieldDefinition) !== ''
            && in_array(
                self::normalizeYamlType(trim((string) ($fieldDefinition['type'] ?? ''))),
                ['combobox', 'table', 'file-upload'],
                true,
            );
    }

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
        return self::isRelationBackedField($fieldDefinition)
            && self::normalizeYamlType(trim((string) ($fieldDefinition['type'] ?? ''))) === 'combobox';
    }

    /**
     * Single-value relation combobox persisted on the parent model (BelongsTo FK semantics).
     *
     * @param  array<string, mixed>  $fieldDefinition
     */
    public static function isSingleRelationCombobox(array $fieldDefinition): bool
    {
        return self::isRelationBackedCombobox($fieldDefinition)
            && ($fieldDefinition['multiple'] ?? false) !== true;
    }

    /**
     * Values for these fields are persisted via relation sync, not mass assignment on the parent.
     *
     * @param  array<string, mixed>  $fieldDefinition
     */
    public static function shouldDeferToRelationSync(array $fieldDefinition): bool
    {
        if (! self::isRelationBackedField($fieldDefinition)) {
            return false;
        }

        $type = self::normalizeYamlType(trim((string) ($fieldDefinition['type'] ?? '')));

        if ($type === 'table') {
            return true;
        }

        if (
            $type === 'file-upload'
            && trim((string) ($fieldDefinition['mode'] ?? 'url')) === 'relation'
        ) {
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
        return self::isRelationBackedField($fieldDefinition)
            && self::normalizeYamlType(trim((string) ($fieldDefinition['type'] ?? ''))) === 'table';
    }

    /**
     * Relation-backed file upload (`type: file-upload`, `mode: relation`, non-empty relation).
     *
     * @param  array<string, mixed>  $fieldDefinition
     */
    public static function isRelationBackedFileUpload(array $fieldDefinition): bool
    {
        return self::isRelationBackedField($fieldDefinition)
            && self::normalizeYamlType(trim((string) ($fieldDefinition['type'] ?? ''))) === 'file-upload'
            && trim((string) ($fieldDefinition['mode'] ?? 'url')) === 'relation';
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private static function relationName(array $fieldDefinition): string
    {
        return isset($fieldDefinition['relation'])
            ? trim((string) $fieldDefinition['relation'])
            : '';
    }
}
