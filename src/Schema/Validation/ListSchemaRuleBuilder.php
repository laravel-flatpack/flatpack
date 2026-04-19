<?php

declare(strict_types=1);

namespace Flatpack\Schema\Validation;

use Flatpack\Schema\RelationFieldQuery;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

/**
 * Builds Laravel validation rules for {@code values.*} from a Flatpack list.yaml schema.
 *
 * Only **editable** columns produce rules. Inline updates are partial: each key defaults to
 * {@code nullable} unless the column sets {@code required: true}.
 *
 * Optional per-column passthrough: {@code rules} as a pipe-separated string or list of rule strings.
 */
final class ListSchemaRuleBuilder
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

        $columns = $schema['columns'] ?? null;
        if (! is_array($columns)) {
            return [];
        }

        $rules = [];
        foreach ($columns as $columnKey => $columnDefinition) {
            if (! is_array($columnDefinition)) {
                continue;
            }

            if (($columnDefinition['editable'] ?? false) !== true) {
                continue;
            }

            $id = trim((string) ($columnDefinition['id'] ?? $columnKey));
            if ($id === '') {
                continue;
            }

            $key = 'values.' . $id;
            $columnRules = $this->rulesForColumn($columnDefinition, $modelClass);

            $passthrough = RuleListParser::parse($columnDefinition['rules'] ?? null);
            if ($passthrough !== []) {
                $columnRules = array_merge($columnRules, $passthrough);
            }

            if ($columnRules !== []) {
                $rules[$key] = $columnRules;
            }
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $columnDefinition
     * @return list<string|\Illuminate\Contracts\Validation\ValidationRule|In>
     */
    private function rulesForColumn(array $columnDefinition, string $modelClass): array
    {
        $required = ($columnDefinition['required'] ?? false) === true;
        $type = $this->normalizedColumnType($columnDefinition);

        $rules = [];

        if ($required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($type === 'relation') {
            $exists = $this->relationExistsRule($modelClass, $columnDefinition);
            if ($exists !== null) {
                $rules[] = $exists;
            } else {
                $rules[] = 'string';
            }

            return $rules;
        }

        if ($type === 'select') {
            $in = $this->selectInRule($columnDefinition);
            if ($in !== null) {
                $rules[] = $in;
            } else {
                $rules[] = 'string';
            }

            return $rules;
        }

        if ($type === 'date' || $type === 'datetime') {
            return array_merge($rules, ['date']);
        }

        if ($type === 'actions') {
            return array_merge($rules, ['array']);
        }

        if (in_array($type, ['text', 'badge', 'status'], true) || $type === '') {
            return array_merge($rules, ['string']);
        }

        return array_merge($rules, ['string']);
    }

    /**
     * @param  array<string, mixed>  $columnDefinition
     */
    private function normalizedColumnType(array $columnDefinition): string
    {
        $columnType = isset($columnDefinition['type']) ? trim((string) $columnDefinition['type']) : 'text';
        if ($columnType === 'datetime') {
            return 'date';
        }

        return $columnType;
    }

    /**
     * @param  array<string, mixed>  $columnDefinition
     */
    private function selectInRule(array $columnDefinition): ?In
    {
        $options = $columnDefinition['options'] ?? null;
        if (! is_array($options)) {
            return null;
        }

        $values = [];
        if (array_is_list($options)) {
            foreach ($options as $option) {
                if (! is_array($option)) {
                    continue;
                }

                $value = isset($option['value']) ? trim((string) $option['value']) : '';
                if ($value !== '') {
                    $values[] = $value;
                }
            }
        } else {
            foreach ($options as $value => $_label) {
                $values[] = trim((string) $value);
            }
        }

        if ($values === []) {
            return null;
        }

        return Rule::in($values);
    }

    /**
     * @param  array<string, mixed>  $columnDefinition
     */
    private function relationExistsRule(string $modelClass, array $columnDefinition): ?string
    {
        $components = RelationFieldQuery::components($modelClass, $columnDefinition);
        if ($components === null) {
            return null;
        }

        [, , $valueField] = $components;
        $related = $components[0]->getModel();
        $table = $related->getTable();

        return 'exists:' . $table . ',' . $valueField;
    }
}
