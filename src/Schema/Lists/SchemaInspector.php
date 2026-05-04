<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists;

use Flatpack\Schema\Forms\FormFieldType;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Schema\YamlSchemaHelper;

final class SchemaInspector
{
    /**
     * @return list<string>
     */
    public static function columnKeys(?array $schema): array
    {
        return array_keys(self::normalizedColumnsById($schema));
    }

    /**
     * @return list<RelationDefinition>
     */
    public static function relationColumnDefinitions(?array $schema): array
    {
        $out = [];
        foreach (self::normalizedColumnsById($schema) as $column) {
            $def = self::parseRelationColumnDefinition($column);
            if ($def !== null) {
                $out[] = $def;
            }
        }

        return $out;
    }

    /**
     * @param  list<RelationDefinition>  $defs
     * @return list<string>
     */
    public static function uniqueRelationNames(array $defs): array
    {
        $names = [];
        foreach ($defs as $def) {
            $names[$def->relation] = true;
        }

        return array_keys($names);
    }

    /**
     * @return list<SearchDefinition>
     */
    public static function searchableColumnDefinitions(?array $schema): array
    {
        $searchableDefs = [];
        foreach (self::normalizedColumnsById($schema) as $id => $column) {
            if (($column['searchable'] ?? false) !== true) {
                continue;
            }

            $type = isset($column['type']) ? trim((string) $column['type']) : '';
            if ($type === 'relation') {
                $relation = isset($column['relation']) ? trim((string) $column['relation']) : '';
                $relationName = YamlSchemaHelper::readString(
                    $column,
                    'relation_name',
                    'relationName',
                );
                if ($relation === '' || $relationName === '') {
                    continue;
                }
                $searchableDefs[] = SearchDefinition::forRelation($relation, $relationName);

                continue;
            }

            $searchableDefs[] = SearchDefinition::forColumn($id);
        }

        return $searchableDefs;
    }

    /**
     * @return list<FilterDefinition>
     */
    public static function filterDefinitions(?array $schema): array
    {
        $filters = $schema['filters'] ?? null;
        if (! is_array($filters) || $filters === []) {
            return [];
        }
        $columnsById = self::normalizedColumnsById($schema);

        $out = [];
        foreach ($filters as $filterId => $filterConfig) {
            $id = trim((string) $filterId);
            if ($id === '') {
                continue;
            }
            $column = $columnsById[$id] ?? null;
            $columnType = self::normalizedColumnType($column);

            $config = is_array($filterConfig) ? $filterConfig : [];
            $configuredType = isset($config['type']) ? trim((string) $config['type']) : '';
            $type = in_array(
                $configuredType,
                CompositionSchemaKeys::LIST_FILTER_TYPES,
                true,
            )
                ? $configuredType
                : $columnType;

            if (! in_array($type, CompositionSchemaKeys::LIST_FILTER_TYPES, true)) {
                continue;
            }

            $label = isset($config['label']) && is_string($config['label'])
                ? trim($config['label'])
                : (is_array($column) && isset($column['label']) ? trim((string) $column['label']) : $id);
            $placeholder = isset($config['placeholder']) && is_string($config['placeholder'])
                ? trim($config['placeholder'])
                : '';

            if ($type === 'select') {
                $options = self::normalizeSelectFilterOptions($config['options'] ?? null);
                if ($options === [] && is_array($column)) {
                    $options = self::normalizeSelectFilterOptions($column['options'] ?? null);
                }
                if ($options === []) {
                    continue;
                }
                $out[] = new FilterDefinition(
                    id: $id,
                    label: $label !== '' ? $label : $id,
                    placeholder: $placeholder,
                    type: 'select',
                    multiple: ($config['multiple'] ?? false) === true,
                    options: $options,
                );

                continue;
            }

            $mode = (($config['mode'] ?? 'exact') === 'from') ? 'from' : 'exact';
            $out[] = new FilterDefinition(
                id: $id,
                label: $label !== '' ? $label : $id,
                placeholder: $placeholder,
                type: 'date',
                multiple: false,
                mode: $mode,
            );
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function sortableColumnIds(?array $schema): array
    {
        $out = [];
        foreach (self::normalizedColumnsById($schema) as $id => $column) {
            if (($column['sortable'] ?? false) === true) {
                $out[] = $id;
            }
        }

        return $out;
    }

    /**
     * For {@code type: image} list columns, resolves {@code file-upload} definitions used by
     * {@see \Flatpack\Services\Uploads\FileUploadBrowserUrl} to emit temporary / signed browse URLs.
     *
     * Definitions come from the list column {@code edit_form_field} when present, otherwise from
     * the merged entity {@code form.yaml} fields ({@code id} or {@code target_column} match).
     *
     * @param  array<string, mixed>|null  $listSchema
     * @param  array<string, mixed>|null  $mergedFormSchema  Output-shaped form schema with merged {@code fields}.
     * @return array<string, array<string, mixed>>
     */
    public static function imageColumnFileUploadDefinitions(
        ?array $listSchema,
        ?array $mergedFormSchema,
    ): array {
        $out = [];
        foreach (self::normalizedColumnsById($listSchema) as $id => $column) {
            if (self::normalizedColumnType($column) !== 'image') {
                continue;
            }

            $edit = self::listColumnInlineEditFormField($column);
            if ($edit !== null && self::isFileUploadFieldDefinition($edit)) {
                $out[$id] = $edit;

                continue;
            }

            if ($mergedFormSchema !== null) {
                $fromForm = self::findFormFileUploadFieldForAttribute($mergedFormSchema, $id);
                if ($fromForm !== null) {
                    $out[$id] = $fromForm;
                }
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $mergedFormSchema
     */
    private static function findFormFileUploadFieldForAttribute(
        array $mergedFormSchema,
        string $attributeKey,
    ): ?array {
        $fields = $mergedFormSchema['fields'] ?? null;
        if (! is_array($fields)) {
            return null;
        }

        foreach ($fields as $yamlKey => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            if (! self::isFileUploadFieldDefinition($definition)) {
                continue;
            }

            $fieldId = trim((string) ($definition['id'] ?? (is_string($yamlKey) ? $yamlKey : '')));
            $targetColumn = trim((string) ($definition['target_column'] ?? ''));

            if ($fieldId === $attributeKey || $targetColumn === $attributeKey) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $column
     * @return array<string, mixed>|null
     */
    private static function listColumnInlineEditFormField(array $column): ?array
    {
        foreach (['edit_form_field', 'editFormField'] as $key) {
            if (isset($column[$key]) && is_array($column[$key])) {
                return $column[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private static function isFileUploadFieldDefinition(array $fieldDefinition): bool
    {
        $type = FormFieldType::normalizeYamlType(trim((string) ($fieldDefinition['type'] ?? '')));

        return $type === 'file-upload';
    }

    /**
     * @param  array<string, mixed>  $column
     */
    private static function parseRelationColumnDefinition(array $column): ?RelationDefinition
    {
        $type = isset($column['type']) ? (string) $column['type'] : '';
        if ($type !== 'relation') {
            return null;
        }

        $relation = isset($column['relation']) ? trim((string) $column['relation']) : '';
        $relationName = YamlSchemaHelper::readString($column, 'relation_name', 'relationName');
        $relationValue = YamlSchemaHelper::readString($column, 'relation_value', 'relationValue');

        if ($relation === '' || $relationName === '' || $relationValue === '') {
            return null;
        }

        return new RelationDefinition($relation, $relationName, $relationValue);
    }

    /**
     * @return list<array{
     *     value: string,
     *     label: string,
     *     status?: 'success'|'pending'|'warning'|'error'|'info',
     *     icon?: string,
     * }>
     */
    private static function normalizeSelectFilterOptions(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        if (array_is_list($raw)) {
            foreach ($raw as $option) {
                if (! is_array($option)) {
                    continue;
                }
                $value = isset($option['value']) ? trim((string) $option['value']) : '';
                $label = isset($option['label']) ? trim((string) $option['label']) : '';
                if ($value === '' || $label === '') {
                    continue;
                }
                $normalizedOption = ['value' => $value, 'label' => $label];
                $status = self::normalizeOptionStatus($option['status'] ?? null);
                if ($status !== null) {
                    $normalizedOption['status'] = $status;
                }
                $icon = self::normalizeOptionIcon($option['icon'] ?? null);
                if ($icon !== null) {
                    $normalizedOption['icon'] = $icon;
                }
                $out[] = $normalizedOption;
            }

            return $out;
        }

        foreach ($raw as $value => $label) {
            if (! is_string($label)) {
                continue;
            }
            $val = trim((string) $value);
            $lab = trim($label);
            if ($val === '' || $lab === '') {
                continue;
            }
            $out[] = ['value' => $val, 'label' => $lab];
        }

        return $out;
    }

    private static function normalizedColumnType(mixed $column): string
    {
        if (! is_array($column)) {
            return '';
        }

        $columnType = isset($column['type']) ? trim((string) $column['type']) : 'text';
        if ($columnType === 'datetime') {
            return 'date';
        }

        return $columnType;
    }

    /**
     * @return 'success'|'pending'|'warning'|'error'|'info'|null
     */
    private static function normalizeOptionStatus(mixed $rawStatus): ?string
    {
        if (! is_string($rawStatus)) {
            return null;
        }

        $status = trim($rawStatus);
        if (! in_array($status, CompositionSchemaKeys::OPTION_STATUS_VALUES, true)) {
            return null;
        }

        return $status;
    }

    private static function normalizeOptionIcon(mixed $rawIcon): ?string
    {
        if (! is_string($rawIcon)) {
            return null;
        }

        $icon = trim($rawIcon);
        if ($icon === '') {
            return null;
        }

        return $icon;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function normalizedColumnsById(?array $schema): array
    {
        $columns = $schema['columns'] ?? null;
        if (! is_array($columns) || $columns === []) {
            return [];
        }

        $out = [];
        if (array_is_list($columns)) {
            foreach ($columns as $column) {
                if (! is_array($column)) {
                    continue;
                }
                $id = isset($column['id']) ? trim((string) $column['id']) : '';
                if ($id === '') {
                    continue;
                }
                $out[$id] = $column;
            }

            return $out;
        }

        foreach ($columns as $key => $column) {
            if (! is_array($column)) {
                continue;
            }
            $id = isset($column['id']) ? trim((string) $column['id']) : trim((string) $key);
            if ($id === '') {
                continue;
            }
            $out[$id] = $column;
        }

        return $out;
    }
}
