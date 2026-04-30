<?php

declare(strict_types=1);

namespace Flatpack\Schema\Validation;

use Flatpack\Schema\RelationFieldQuery;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

final class SchemaFieldRuleHelper
{
    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    public static function selectInRule(array $fieldDefinition): ?In
    {
        $options = $fieldDefinition['options'] ?? null;
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
     * @param  array<string, mixed>  $fieldDefinition
     */
    public static function relationExistsRule(string $modelClass, array $fieldDefinition): ?string
    {
        $components = RelationFieldQuery::components($modelClass, $fieldDefinition);
        if ($components === null) {
            return null;
        }

        [, , $valueField] = $components;
        $related = $components[0]->getModel();
        $table = $related->getTable();

        return 'exists:' . $table . ',' . $valueField;
    }
}
