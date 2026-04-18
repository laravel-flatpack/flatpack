<?php

declare(strict_types=1);

namespace Flatpack\Support;

use Flatpack\Schema\FormFieldType;
use Illuminate\Database\Eloquent\Model;

/**
 * Prepares form YAML schema and field values for Inertia / JSON responses.
 */
final class FormSchemaNormalizer
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    public function normalizedFormSchema(?array $schema): ?array
    {
        if ($schema === null) {
            return null;
        }

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return $schema;
        }

        $normalizedFields = [];
        foreach ($fields as $fieldId => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                $normalizedFields[$fieldId] = $fieldDefinition;

                continue;
            }

            $normalizedFields[$fieldId] = $this->normalizedFieldDefinition($fieldDefinition);
        }

        $normalized = $schema;
        $normalized['fields'] = $normalizedFields;

        return $normalized;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    public function formValuesFromModel(?Model $model, ?array $schema): array
    {
        if (! $model instanceof Model || $schema === null) {
            return [];
        }

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return [];
        }

        $values = [];
        foreach ($fields as $fieldId => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }

            $id = trim((string) ($fieldDefinition['id'] ?? $fieldId));
            if ($id === '') {
                continue;
            }

            $values[$id] = $model->getAttribute($id);
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>
     */
    private function normalizedFieldDefinition(array $fieldDefinition): array
    {
        $rawType = isset($fieldDefinition['type'])
            ? trim((string) $fieldDefinition['type'])
            : '';

        $fieldDefinition['type'] = FormFieldType::normalizeYamlType($rawType);

        if ($rawType === 'relation') {
            $fieldDefinition['type'] = 'combobox';
            $fieldDefinition['options'] = [];
            $fieldDefinition['remote'] = true;
        } elseif ($rawType === 'select' || $rawType === 'combobox') {
            $fieldDefinition['options'] = $this->normalizeFieldOptions(
                $fieldDefinition['options'] ?? null,
            );
        }

        if (isset($fieldDefinition['preset'])) {
            $normalizedPreset = $this->normalizePreset($fieldDefinition['preset']);
            if ($normalizedPreset !== null) {
                $fieldDefinition['preset'] = $normalizedPreset;
            } else {
                unset($fieldDefinition['preset']);
            }
        }

        return $fieldDefinition;
    }

    /**
     * @param  mixed  $preset
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

        $allowed = ['exact', 'slug', 'url', 'camel', 'file'];
        if ($field === '' || ! in_array($type, $allowed, true)) {
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
}
