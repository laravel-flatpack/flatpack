<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms\Normalization;

use Flatpack\Schema\Forms\FormFieldType;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Support\CompositionDebugLog;

/**
 * Normalizes a single form field definition and drops fields with unsupported types.
 */
final class FormFieldDefinitionNormalizer
{
    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>|null
     */
    public function normalize(
        array $fieldDefinition,
        string $yamlKey,
        ?CompositionDebugLog $log,
    ): ?array {
        $rawType = isset($fieldDefinition['type'])
            ? trim((string) $fieldDefinition['type'])
            : '';

        $fieldDefinition['type'] = FormFieldType::normalizeYamlType($rawType);

        if ($fieldDefinition['type'] === '') {
            $fieldDefinition['type'] = 'text';
        }

        if ($fieldDefinition['type'] === 'table') {
            $relation = isset($fieldDefinition['relation'])
                ? trim((string) $fieldDefinition['relation'])
                : '';
            $model = isset($fieldDefinition['model'])
                ? trim((string) $fieldDefinition['model'])
                : '';
            $provider = isset($fieldDefinition['provider'])
                ? trim((string) $fieldDefinition['provider'])
                : '';
            if ($provider !== '') {
                $tableLabel = $this->fieldDisplayLabel($fieldDefinition, $yamlKey);
                $log?->add(sprintf(
                    'Form field "%s": table provider is not supported (field omitted).',
                    $tableLabel,
                ));

                return null;
            }
            if ($relation !== '' && $model !== '') {
                $tableLabel = $this->fieldDisplayLabel($fieldDefinition, $yamlKey);
                $log?->add(sprintf(
                    'Form field "%s": table cannot define both model and relation (field omitted).',
                    $tableLabel,
                ));

                return null;
            }
            if ($relation !== '') {
                $columns = $fieldDefinition['columns'] ?? null;
                if (! $this->relationTableColumnsAreNonEmpty($columns)) {
                    $tableLabel = $this->fieldDisplayLabel($fieldDefinition, $yamlKey);
                    $log?->add(sprintf(
                        'Form field "%s": table with relation requires non-empty columns (field omitted).',
                        $tableLabel,
                    ));

                    return null;
                }

                $relationValue = isset($fieldDefinition['relation_value'])
                    ? trim((string) $fieldDefinition['relation_value'])
                    : '';
                if ($relationValue === '') {
                    $fieldDefinition['relation_value'] = 'id';
                }
            }
        } elseif ($rawType === 'select' || $rawType === 'combobox') {
            $fieldDefinition['options'] = $this->normalizeFieldOptions(
                $fieldDefinition['options'] ?? null,
            );
            if ($fieldDefinition['type'] === 'combobox') {
                $relation = isset($fieldDefinition['relation'])
                    ? trim((string) $fieldDefinition['relation'])
                    : '';
                if ($relation !== '') {
                    $fieldDefinition['remote'] = true;
                }
            }
        } elseif ($fieldDefinition['type'] === 'file-upload') {
            $mode = isset($fieldDefinition['mode'])
                ? trim((string) $fieldDefinition['mode'])
                : 'url';
            if (! in_array($mode, ['relation', 'url'], true)) {
                $mode = 'url';
            }
            $fieldDefinition['mode'] = $mode;

            $fieldDefinition['multiple'] = ($fieldDefinition['multiple'] ?? false) === true;

            if (isset($fieldDefinition['max_files']) && (int) $fieldDefinition['max_files'] > 0) {
                $fieldDefinition['max_files'] = (int) $fieldDefinition['max_files'];
            }

            if (isset($fieldDefinition['max_size_kb']) && (int) $fieldDefinition['max_size_kb'] > 0) {
                $fieldDefinition['max_size_kb'] = (int) $fieldDefinition['max_size_kb'];
            }

            if ($mode === 'relation') {
                $relation = isset($fieldDefinition['relation'])
                    ? trim((string) $fieldDefinition['relation'])
                    : '';
                if ($relation === '') {
                    $label = $this->fieldDisplayLabel($fieldDefinition, $yamlKey);
                    $log?->add(sprintf(
                        'Form field "%s": file-upload with relation mode requires relation (field omitted).',
                        $label,
                    ));

                    return null;
                }
            } else {
                $targetColumn = isset($fieldDefinition['target_column'])
                    ? trim((string) $fieldDefinition['target_column'])
                    : '';
                if ($targetColumn === '') {
                    $fieldDefinition['target_column'] = $this->fieldDisplayLabel($fieldDefinition, $yamlKey);
                }
            }
        }

        $canonicalType = $fieldDefinition['type'];
        $label = $this->fieldDisplayLabel($fieldDefinition, $yamlKey);
        $hadPreset = array_key_exists('preset', $fieldDefinition);

        if ($canonicalType !== 'text' && $canonicalType !== 'textarea') {
            if ($hadPreset) {
                $log?->add(sprintf('Form field "%s": removed preset (only text and textarea support preset).', $label));
                unset($fieldDefinition['preset']);
            }
        } elseif ($hadPreset) {
            $preset = $fieldDefinition['preset'];
            $normalizedPreset = $this->normalizePreset($preset);
            if ($normalizedPreset !== null) {
                $fieldDefinition['preset'] = $normalizedPreset;
            } else {
                $detail = $this->describeInvalidPreset($preset);
                $log?->add(sprintf('Form field "%s": removed invalid preset (%s).', $label, $detail));
                unset($fieldDefinition['preset']);
            }
        }

        if (! in_array($fieldDefinition['type'], CompositionSchemaKeys::FORM_FIELD_TYPES_CANONICAL, true)) {
            $typeLabel = $rawType !== '' ? $rawType : (string) $fieldDefinition['type'];
            $log?->add(sprintf(
                'Form field "%s": unknown type "%s" (field omitted from schema).',
                $label,
                $typeLabel,
            ));

            return null;
        }

        return $fieldDefinition;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    public function fieldDisplayLabel(array $fieldDefinition, string $yamlKey): string
    {
        $id = trim((string) ($fieldDefinition['id'] ?? $yamlKey));

        return $id !== '' ? $id : $yamlKey;
    }

    private function describeInvalidPreset(mixed $preset): string
    {
        if (! is_array($preset)) {
            return 'expected an object with string field and type';
        }

        $field = isset($preset['field']) && is_string($preset['field'])
            ? trim($preset['field'])
            : '';
        $type = isset($preset['type']) && is_string($preset['type'])
            ? trim($preset['type'])
            : '';

        if ($field === '') {
            return 'missing source field name';
        }

        if ($type === '' || ! in_array($type, CompositionSchemaKeys::FORM_PRESET_TYPES, true)) {
            return sprintf('type must be one of: %s', implode(', ', CompositionSchemaKeys::FORM_PRESET_TYPES));
        }

        return 'invalid preset shape';
    }

    /**
     * @return array{field: string, type: string}|null
     */
    private function normalizePreset(mixed $preset): ?array
    {
        if (! is_array($preset)) {
            return null;
        }

        $field = isset($preset['field']) && is_string($preset['field'])
            ? trim($preset['field'])
            : '';
        $type = isset($preset['type']) && is_string($preset['type'])
            ? trim($preset['type'])
            : '';

        if ($field === '' || ! in_array($type, CompositionSchemaKeys::FORM_PRESET_TYPES, true)) {
            return null;
        }

        return ['field' => $field, 'type' => $type];
    }

    /**
     * @return list<array{value: string, label: string, status?: string, icon?: string}>
     */
    private function normalizeFieldOptions(mixed $raw): array
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

                $normalized = ['value' => $value, 'label' => $label];
                if (isset($option['status']) && is_string($option['status']) && trim($option['status']) !== '') {
                    $normalized['status'] = trim($option['status']);
                }
                if (isset($option['icon']) && is_string($option['icon']) && trim($option['icon']) !== '') {
                    $normalized['icon'] = trim($option['icon']);
                }
                $out[] = $normalized;
            }

            return $out;
        }

        foreach ($raw as $value => $label) {
            if (! is_string($label)) {
                continue;
            }

            $normalizedValue = trim((string) $value);
            $normalizedLabel = trim($label);
            if ($normalizedValue === '' || $normalizedLabel === '') {
                continue;
            }

            $out[] = [
                'value' => $normalizedValue,
                'label' => $normalizedLabel,
            ];
        }

        return $out;
    }

    /**
     * Accepts list.yaml-style columns: array of defs (each with {@code id}) or map keyed by column id.
     */
    private function relationTableColumnsAreNonEmpty(mixed $columns): bool
    {
        if (! is_array($columns) || $columns === []) {
            return false;
        }

        if (array_is_list($columns)) {
            foreach ($columns as $column) {
                if (! is_array($column)) {
                    continue;
                }
                if (trim((string) ($column['id'] ?? '')) !== '') {
                    return true;
                }
            }

            return false;
        }

        foreach ($columns as $key => $_def) {
            if (trim((string) $key) !== '') {
                return true;
            }
        }

        return false;
    }
}
