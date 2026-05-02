<?php

declare(strict_types=1);

namespace Flatpack\Schema\Validation;

use Flatpack\Schema\CompositionTabsMerge;
use Flatpack\Schema\Forms\FormFieldType;
use Illuminate\Validation\Rules\In;

/**
 * Builds Laravel validation rules for {@code values.*} from a Flatpack form.yaml schema.
 *
 * Optional per-field passthrough: {@code rules} as a pipe-separated string or list of rule strings.
 */
final class FormSchemaRuleBuilder
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, list<string|\Illuminate\Contracts\Validation\ValidationRule|In>>
     */
    public function rulesForValues(?array $schema, string $modelClass): array
    {
        if ($schema === null) {
            return [];
        }

        $schema = CompositionTabsMerge::form($schema) ?? $schema;

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return [];
        }

        $rules = [];
        foreach ($fields as $fieldId => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }

            $id = trim((string) ($fieldDefinition['id'] ?? $fieldId));
            if ($id === '') {
                continue;
            }

            $key = 'values.' . $id;
            $fieldRules = $this->rulesForField(
                $fieldDefinition,
                $modelClass,
            );

            $passthrough = $this->passthroughRules($fieldDefinition);
            if ($passthrough !== []) {
                $fieldRules = array_merge($fieldRules, $passthrough);
            }

            if ($fieldRules !== []) {
                $rules[$key] = $fieldRules;
            }
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return list<string|\Illuminate\Contracts\Validation\ValidationRule|In>
     */
    private function rulesForField(array $fieldDefinition, string $modelClass): array
    {
        $required = ($fieldDefinition['required'] ?? false) === true;
        $type = FormFieldType::normalizeYamlType(
            trim((string) ($fieldDefinition['type'] ?? '')),
        );

        $rules = [];

        if ($required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if (FormFieldType::shouldDeferToRelationSync($fieldDefinition)) {
            return array_merge($rules, ['array']);
        }

        if (FormFieldType::isSingleRelationCombobox($fieldDefinition)) {
            $exists = SchemaFieldRuleHelper::relationExistsRule($modelClass, $fieldDefinition);
            if ($exists !== null) {
                $rules[] = $exists;
            } else {
                $rules[] = 'string';
            }

            return $rules;
        }

        if ($type === 'select') {
            $in = SchemaFieldRuleHelper::selectInRule($fieldDefinition);
            if ($in !== null) {
                $rules[] = $in;
            } else {
                $rules[] = 'string';
            }

            return $rules;
        }

        if ($type === 'combobox') {
            $multi = ($fieldDefinition['multiple'] ?? false) === true;
            if ($multi) {
                $rules[] = 'array';
            } else {
                $rules[] = 'string';
            }

            return $rules;
        }

        if ($type === 'file-upload') {
            $multi = ($fieldDefinition['multiple'] ?? false) === true;
            if ($multi) {
                $rules[] = 'array';
            } else {
                $rules[] = 'array';
            }

            return $rules;
        }

        return match ($type) {
            'text', 'textarea', 'rich-text', 'block-editor' => array_merge($rules, ['string']),
            'checkbox', 'switch' => array_merge($rules, ['boolean']),
            'date-picker' => array_merge($rules, ['date']),
            'date-range-picker' => array_merge($rules, ['array']),
            'time-picker' => array_merge($rules, ['array']),
            'table' => array_merge($rules, ['array']),
            'repeater' => array_merge($rules, ['array']),
            default => array_merge($rules, ['string']),
        };
    }

    /**
     * Optional YAML {@code rules} key: pipe-separated string or list of rule strings.
     *
     * @param  array<string, mixed>  $fieldDefinition
     * @return list<string|\Illuminate\Contracts\Validation\ValidationRule>
     */
    private function passthroughRules(array $fieldDefinition): array
    {
        return RuleListParser::parse($fieldDefinition['rules'] ?? null);
    }
}
