<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms\Normalization;

use Flatpack\Schema\Forms\FormFieldType;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Schema\HeaderActions;
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
        $rawType = $this->readTrimmedString($fieldDefinition, 'type');
        $canonicalType = FormFieldType::normalizeYamlType($rawType);
        if ($canonicalType === '') {
            $canonicalType = $this->inferToolbarFromActionsBlock($fieldDefinition)
                ? 'toolbar'
                : CompositionSchemaKeys::FORM_DEFAULT_FIELD_TYPE;
        }
        $fieldDefinition['type'] = $canonicalType;

        $fieldDefinition = $this->normalizeByType($fieldDefinition, $yamlKey, $log);
        if ($fieldDefinition === null) {
            return null;
        }

        $fieldDefinition = $this->normalizePresetRules($fieldDefinition, $yamlKey, $log);

        $label = $this->fieldDisplayLabel($fieldDefinition, $yamlKey);
        FieldSpanCanonicalizer::applyToDefinition(
            $fieldDefinition,
            sprintf('Form field "%s"', $label),
            $log,
        );
        FieldsetCanonicalizer::applyToDefinition($fieldDefinition, $log);
        $this->normalizeNestedSpansInFieldDefinition($fieldDefinition, $yamlKey, $log);

        if (! in_array($canonicalType, CompositionSchemaKeys::FORM_FIELD_TYPES_CANONICAL, true)) {
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

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>|null
     */
    private function normalizeByType(
        array $fieldDefinition,
        string $yamlKey,
        ?CompositionDebugLog $log,
    ): ?array {
        return match ($fieldDefinition['type']) {
            'table' => $this->normalizeTableField($fieldDefinition, $yamlKey, $log),
            'toolbar' => $this->normalizeToolbarField($fieldDefinition, $yamlKey, $log),
            'select', 'combobox' => $this->normalizeChoiceField($fieldDefinition),
            'rich-text', 'block-editor' => $this->normalizeEditorUpload($fieldDefinition, $yamlKey, $log),
            'file-upload' => $this->normalizeFileUploadField($fieldDefinition, $yamlKey, $log),
            default => $fieldDefinition,
        };
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function inferToolbarFromActionsBlock(array $fieldDefinition): bool
    {
        $actions = $fieldDefinition['actions'] ?? null;
        if (! is_array($actions) || $actions === []) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>|null
     */
    private function normalizeToolbarField(
        array $fieldDefinition,
        string $yamlKey,
        ?CompositionDebugLog $log,
    ): ?array {
        $rawActions = $fieldDefinition['actions'] ?? null;
        if (! is_array($rawActions)) {
            return $this->omitFieldWithLog(
                $fieldDefinition,
                $yamlKey,
                $log,
                'toolbar requires actions',
            );
        }

        $debugPath = sprintf('fields.%s.actions', $yamlKey);
        $normalized = HeaderActions::fromActionsBlock($rawActions, $log, $yamlKey, $debugPath, false);
        if ($normalized === []) {
            return $this->omitFieldWithLog(
                $fieldDefinition,
                $yamlKey,
                $log,
                'toolbar has no usable actions after normalization',
            );
        }

        $fieldDefinition['actions'] = $normalized;
        $fieldDefinition['align'] = $this->normalizeToolbarAlign($fieldDefinition['align'] ?? null);

        return $fieldDefinition;
    }

    private function normalizeToolbarAlign(mixed $raw): string
    {
        $allowed = ['left', 'right', 'center', 'start', 'end', 'spaced'];
        if (! is_string($raw)) {
            return 'right';
        }
        $v = trim($raw);

        return in_array($v, $allowed, true) ? $v : 'right';
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>|null
     */
    private function normalizeTableField(
        array $fieldDefinition,
        string $yamlKey,
        ?CompositionDebugLog $log,
    ): ?array {
        $relation = $this->readTrimmedString($fieldDefinition, 'relation');
        $model = $this->readTrimmedString($fieldDefinition, 'model');
        $provider = $this->readTrimmedString($fieldDefinition, 'provider');

        if ($provider !== '') {
            return $this->omitFieldWithLog(
                $fieldDefinition,
                $yamlKey,
                $log,
                'table provider is not supported',
            );
        }

        if ($relation !== '' && $model !== '') {
            return $this->omitFieldWithLog(
                $fieldDefinition,
                $yamlKey,
                $log,
                'table cannot define both model and relation',
            );
        }

        if ($relation === '') {
            return $fieldDefinition;
        }

        $columns = $fieldDefinition['columns'] ?? null;
        if (! $this->relationTableColumnsAreNonEmpty($columns)) {
            return $this->omitFieldWithLog(
                $fieldDefinition,
                $yamlKey,
                $log,
                'table with relation requires non-empty columns',
            );
        }

        if ($this->readTrimmedString($fieldDefinition, 'relation_value') === '') {
            $fieldDefinition['relation_value'] = 'id';
        }

        return $fieldDefinition;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>
     */
    private function normalizeChoiceField(array $fieldDefinition): array
    {
        $fieldDefinition['options'] = $this->normalizeFieldOptions(
            $fieldDefinition['options'] ?? null,
        );

        if (
            $fieldDefinition['type'] === 'combobox'
            && $this->readTrimmedString($fieldDefinition, 'relation') !== ''
        ) {
            $fieldDefinition['remote'] = true;
        }

        return $fieldDefinition;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>|null
     */
    private function normalizeFileUploadField(
        array $fieldDefinition,
        string $yamlKey,
        ?CompositionDebugLog $log,
    ): ?array {
        $upload = $fieldDefinition['upload'] ?? null;
        if (! is_array($upload)) {
            return $this->omitFieldWithLog(
                $fieldDefinition,
                $yamlKey,
                $log,
                'file-upload requires upload object',
            );
        }

        $mode = $this->readTrimmedString($fieldDefinition, 'mode', 'url');
        if (! in_array($mode, ['relation', 'url', 'image', 'file'], true)) {
            $mode = 'url';
        }
        $fieldDefinition['mode'] = $mode;
        $normalizedUpload = $this->normalizeUploadConfig($upload);
        $fieldDefinition['upload'] = $normalizedUpload;
        $fieldDefinition = array_merge($fieldDefinition, $normalizedUpload);

        if ($mode === 'relation') {
            if ($this->readTrimmedString($fieldDefinition, 'relation') === '') {
                return $this->omitFieldWithLog(
                    $fieldDefinition,
                    $yamlKey,
                    $log,
                    'file-upload with relation mode requires relation',
                );
            }

            $callback = $this->readTrimmedString($fieldDefinition, 'callback');
            if ($callback === '') {
                return $this->omitFieldWithLog(
                    $fieldDefinition,
                    $yamlKey,
                    $log,
                    'file-upload relation mode requires callback (public method name on the model)',
                );
            }

            $fieldDefinition['callback'] = $callback;

            return $fieldDefinition;
        }

        if ($this->readTrimmedString($fieldDefinition, 'target_column') === '') {
            $fieldDefinition['target_column'] = $this->fieldDisplayLabel($fieldDefinition, $yamlKey);
        }

        return $fieldDefinition;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>
     */
    private function normalizeEditorUpload(
        array $fieldDefinition,
        string $yamlKey,
        ?CompositionDebugLog $log,
    ): array {
        if (! array_key_exists('upload', $fieldDefinition)) {
            return $fieldDefinition;
        }

        $upload = $fieldDefinition['upload'];
        if (! is_array($upload)) {
            $log?->add(sprintf(
                'Form field "%s": removed invalid upload (expected object).',
                $this->fieldDisplayLabel($fieldDefinition, $yamlKey),
            ));
            unset($fieldDefinition['upload']);

            return $fieldDefinition;
        }

        $normalized = $this->normalizeUploadConfig($upload);
        unset(
            $normalized['mode'],
            $normalized['relation'],
            $normalized['callback'],
            $normalized['target_column'],
            $normalized['persist_as'],
        );

        if (isset($normalized['accept'])) {
            if (is_string($normalized['accept'])) {
                $accept = trim($normalized['accept']);
                if ($accept === '') {
                    unset($normalized['accept']);
                } else {
                    $normalized['accept'] = $accept;
                }
            } elseif (is_array($normalized['accept'])) {
                $tokens = [];
                foreach ($normalized['accept'] as $token) {
                    if (! is_string($token)) {
                        continue;
                    }
                    $trimmed = trim($token);
                    if ($trimmed !== '') {
                        $tokens[] = $trimmed;
                    }
                }
                if ($tokens === []) {
                    unset($normalized['accept']);
                } else {
                    $normalized['accept'] = array_values(array_unique($tokens));
                }
            } else {
                unset($normalized['accept']);
            }
        }

        foreach (['disk', 'directory', 'collection'] as $key) {
            if (! array_key_exists($key, $normalized)) {
                continue;
            }
            $value = trim((string) $normalized[$key]);
            if ($value === '') {
                unset($normalized[$key]);

                continue;
            }
            $normalized[$key] = $value;
        }

        if (array_key_exists('visibility', $normalized)) {
            $visibility = trim((string) $normalized['visibility']);
            if ($visibility !== 'public' && $visibility !== 'private') {
                unset($normalized['visibility']);
            } else {
                $normalized['visibility'] = $visibility;
            }
        }

        $fieldDefinition['upload'] = $normalized;

        return $fieldDefinition;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>
     */
    private function normalizeUploadConfig(array $fieldDefinition): array
    {
        $fieldDefinition['multiple'] = ($fieldDefinition['multiple'] ?? false) === true;

        if (isset($fieldDefinition['max_files']) && (int) $fieldDefinition['max_files'] > 0) {
            $fieldDefinition['max_files'] = (int) $fieldDefinition['max_files'];
        } else {
            unset($fieldDefinition['max_files']);
        }

        if (isset($fieldDefinition['max_size_kb']) && (int) $fieldDefinition['max_size_kb'] > 0) {
            $fieldDefinition['max_size_kb'] = (int) $fieldDefinition['max_size_kb'];
        } else {
            unset($fieldDefinition['max_size_kb']);
        }

        return $fieldDefinition;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>
     */
    private function normalizePresetRules(
        array $fieldDefinition,
        string $yamlKey,
        ?CompositionDebugLog $log,
    ): array {
        if (! array_key_exists('preset', $fieldDefinition)) {
            return $fieldDefinition;
        }

        $label = $this->fieldDisplayLabel($fieldDefinition, $yamlKey);
        $canonicalType = (string) ($fieldDefinition['type'] ?? '');
        if ($canonicalType !== 'text' && $canonicalType !== 'textarea') {
            $log?->add(sprintf('Form field "%s": removed preset (only text and textarea support preset).', $label));
            unset($fieldDefinition['preset']);

            return $fieldDefinition;
        }

        $parsedPreset = $this->parsePreset($fieldDefinition['preset']);
        if (is_array($parsedPreset)) {
            $fieldDefinition['preset'] = $parsedPreset;

            return $fieldDefinition;
        }

        $log?->add(sprintf('Form field "%s": removed invalid preset (%s).', $label, $parsedPreset));
        unset($fieldDefinition['preset']);

        return $fieldDefinition;
    }

    /**
     * @return array{field: string, type: string}|string
     */
    private function parsePreset(mixed $preset): array|string
    {
        if (! is_array($preset)) {
            return 'expected an object with string field and type';
        }

        $field = isset($preset['field']) && is_string($preset['field']) ? trim($preset['field']) : '';
        $type = isset($preset['type']) && is_string($preset['type']) ? trim($preset['type']) : '';

        if ($field === '') {
            return 'missing source field name';
        }

        if ($type === '' || ! in_array($type, CompositionSchemaKeys::FORM_PRESET_TYPES, true)) {
            return sprintf('type must be one of: %s', implode(', ', CompositionSchemaKeys::FORM_PRESET_TYPES));
        }

        return ['field' => $field, 'type' => $type];
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function readTrimmedString(array $fieldDefinition, string $key, string $default = ''): string
    {
        if (! array_key_exists($key, $fieldDefinition)) {
            return $default;
        }

        return trim((string) $fieldDefinition[$key]);
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function omitFieldWithLog(
        array $fieldDefinition,
        string $yamlKey,
        ?CompositionDebugLog $log,
        string $reason,
    ): null {
        $label = $this->fieldDisplayLabel($fieldDefinition, $yamlKey);
        $log?->add(sprintf('Form field "%s": %s (field omitted).', $label, $reason));

        return null;
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

    /**
     * Canonicalizes `span` on nested structures (repeater item fields, embedded table edit fields, nested widgets).
     *
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function normalizeNestedSpansInFieldDefinition(
        array &$fieldDefinition,
        string $yamlKey,
        ?CompositionDebugLog $log,
    ): void {
        $type = isset($fieldDefinition['type']) ? trim((string) $fieldDefinition['type']) : '';

        if ($type === 'repeater') {
            $parentLabel = $this->fieldDisplayLabel($fieldDefinition, $yamlKey);
            $inlineFields = $fieldDefinition['fields'] ?? null;
            if (is_array($inlineFields)) {
                foreach ($inlineFields as $nestedKey => &$nested) {
                    if (! is_array($nested)) {
                        continue;
                    }
                    $nestedYamlKey = is_string($nestedKey) ? $nestedKey : (string) $nestedKey;
                    $nl = $this->fieldDisplayLabel($nested, $nestedYamlKey);
                    FieldSpanCanonicalizer::applyToDefinition(
                        $nested,
                        sprintf('Form repeater "%s" item field "%s"', $parentLabel, $nl),
                        $log,
                    );
                    FieldsetCanonicalizer::applyToDefinition($nested, $log);
                    $this->normalizeNestedSpansInFieldDefinition($nested, $nestedYamlKey, $log);
                }
                unset($nested);
            }

            $groups = $fieldDefinition['groups'] ?? null;
            if (is_array($groups)) {
                foreach ($groups as $groupKey => &$group) {
                    if (! is_array($group)) {
                        continue;
                    }
                    $innerFields = $group['fields'] ?? null;
                    if (! is_array($innerFields)) {
                        continue;
                    }
                    foreach ($innerFields as $nestedKey => &$nested) {
                        if (! is_array($nested)) {
                            continue;
                        }
                        $nestedYamlKey = is_string($nestedKey) ? $nestedKey : (string) $nestedKey;
                        $nl = $this->fieldDisplayLabel($nested, $nestedYamlKey);
                        FieldSpanCanonicalizer::applyToDefinition(
                            $nested,
                            sprintf(
                                'Form repeater "%s" group "%s" item field "%s"',
                                $parentLabel,
                                is_string($groupKey) ? $groupKey : (string) $groupKey,
                                $nl,
                            ),
                            $log,
                        );
                        FieldsetCanonicalizer::applyToDefinition($nested, $log);
                        $this->normalizeNestedSpansInFieldDefinition($nested, $nestedYamlKey, $log);
                    }
                    unset($nested);
                }
                unset($group);
            }
        }

        if ($type === 'table') {
            if (! isset($fieldDefinition['columns']) || ! is_array($fieldDefinition['columns'])) {
                return;
            }

            foreach ($fieldDefinition['columns'] as $columnKey => &$column) {
                if (! is_array($column)) {
                    continue;
                }
                $colId = is_string($columnKey) ? $columnKey : (string) $columnKey;
                foreach (['edit_form_field', 'editFormField'] as $editKey) {
                    if (! isset($column[$editKey]) || ! is_array($column[$editKey])) {
                        continue;
                    }
                    FieldSpanCanonicalizer::applyToDefinition(
                        $column[$editKey],
                        sprintf('Embedded table column "%s" edit field (%s)', $colId, $editKey),
                        $log,
                    );
                    FieldsetCanonicalizer::applyToDefinition($column[$editKey], $log);
                }
            }
            unset($column);
        }

        if ($type === 'widget') {
            $widgets = $fieldDefinition['widget'] ?? null;
            if (! is_array($widgets)) {
                return;
            }
            $parentLabel = $this->fieldDisplayLabel($fieldDefinition, $yamlKey);
            foreach ($widgets as $widgetId => &$widgetDef) {
                if (! is_array($widgetDef)) {
                    continue;
                }
                $wid = is_string($widgetId) ? $widgetId : (string) $widgetId;
                FieldSpanCanonicalizer::applyToDefinition(
                    $widgetDef,
                    sprintf('Form field "%s" nested widget "%s"', $parentLabel, $wid),
                    $log,
                );
            }
            unset($widgetDef);
        }
    }
}
