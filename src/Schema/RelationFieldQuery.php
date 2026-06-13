<?php

declare(strict_types=1);

namespace Flatpack\Schema;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Resolves Eloquent relation query plus label/value columns from form or list YAML
 * ({@code relation}, {@code relation_name}/{@code relationName}, {@code relation_value}/{@code relationValue}).
 */
final class RelationFieldQuery
{
    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array{0: Builder<Model>, 1: string, 2: string}|null
     */
    public static function components(string $modelClass, array $fieldDefinition): ?array
    {
        if ($modelClass === '' || ! class_exists($modelClass)) {
            return null;
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        $relationName = isset($fieldDefinition['relation'])
            ? trim((string) $fieldDefinition['relation'])
            : '';
        $labelField = YamlSchemaHelper::readString($fieldDefinition, 'relation_name', 'relationName');
        $valueField = YamlSchemaHelper::readString($fieldDefinition, 'relation_value', 'relationValue');

        if ($relationName === '' || $labelField === '' || $valueField === '') {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $model = new $modelClass();
        if (! method_exists($model, $relationName)) {
            return null;
        }

        $relation = $model->{$relationName}();
        if (! $relation instanceof Relation) {
            return null;
        }

        return [$relation->getRelated()->newQuery(), $labelField, $valueField];
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    public static function stringFromField(
        array $fieldDefinition,
        string $snakeKey,
        string $camelKey,
    ): string {
        return YamlSchemaHelper::readString($fieldDefinition, $snakeKey, $camelKey);
    }
}
