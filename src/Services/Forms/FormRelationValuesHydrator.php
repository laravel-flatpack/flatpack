<?php

declare(strict_types=1);

namespace Flatpack\Services\Forms;

use Flatpack\Schema\RelationFieldQuery;
use Flatpack\Support\CompositionDebugLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Builds {@code values.*} for relation-sync fields from loaded Eloquent relations (RelationRow-shaped rows).
 */
final class FormRelationValuesHydrator
{
    /**
     * Resolves attribute keys to hydrate on each related model. Supports the same shapes as list.yaml:
     * list of column defs (each with {@code id}), or map keyed by column id -> def.
     *
     * @param  array<string, mixed>  $fieldDefinition
     * @return list<string>
     */
    public static function columnIdsFromDefinition(array $fieldDefinition): array
    {
        $columns = $fieldDefinition['columns'] ?? null;
        if (! is_array($columns)) {
            return [];
        }

        if ($columns === []) {
            return [];
        }

        // List style: [ { id: name, label, ... }, ... ]
        if (array_is_list($columns)) {
            $ids = [];
            foreach ($columns as $column) {
                if (! is_array($column)) {
                    continue;
                }
                $id = trim((string) ($column['id'] ?? ''));
                if ($id !== '') {
                    $ids[] = $id;
                }
            }

            return $ids;
        }

        // Map style (list.yaml): { content: { label, type }, user_id: { ... } }
        $ids = [];
        foreach ($columns as $key => $_def) {
            $id = trim((string) $key);
            if ($id !== '') {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * Nested relation names declared on {@code type: relation} columns (for eager-loading {@code parent.child}).
     *
     * @param  array<string, mixed>  $fieldDefinition
     * @return list<string>
     */
    public static function nestedRelationNamesForEagerLoad(array $fieldDefinition): array
    {
        $seen = [];
        foreach (self::columnDefinitionsList($fieldDefinition) as $columnDef) {
            $type = isset($columnDef['type']) ? trim((string) $columnDef['type']) : '';
            if ($type !== 'relation') {
                continue;
            }
            $nested = isset($columnDef['relation']) ? trim((string) $columnDef['relation']) : '';
            if ($nested !== '') {
                $seen[$nested] = true;
            }
        }

        return array_keys($seen);
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return list<array<string, mixed>>
     */
    public function hydrate(
        Model $model,
        string $fieldId,
        array $fieldDefinition,
        ?CompositionDebugLog $log,
    ): array {
        $relationName = isset($fieldDefinition['relation'])
            ? trim((string) $fieldDefinition['relation'])
            : '';
        if ($relationName === '') {
            return [];
        }

        if (! method_exists($model, $relationName)) {
            $log?->add(sprintf(
                'Form field "%s": model [%s] has no relation method [%s].',
                $fieldId,
                $model::class,
                $relationName,
            ));

            return [];
        }

        /** @var Relation $relation */
        $relation = $model->{$relationName}();

        if ($relation instanceof BelongsToMany) {
            return $this->hydrateRelatedCollection($model, $fieldId, $relationName, $fieldDefinition, $log);
        }

        if ($relation instanceof HasMany || $relation instanceof MorphMany) {
            return $this->hydrateRelatedCollection($model, $fieldId, $relationName, $fieldDefinition, $log);
        }
        if ($relation instanceof HasOne) {
            return $this->hydrateRelatedSingle($model, $fieldId, $relationName, $fieldDefinition, $log);
        }

        $log?->add(sprintf(
            'Form field "%s": relation [%s] (%s) is not supported for hydration yet.',
            $fieldId,
            $relationName,
            $relation::class,
        ));

        return [];
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return list<array<string, mixed>>
     */
    private static function columnDefinitionsList(array $fieldDefinition): array
    {
        $columns = $fieldDefinition['columns'] ?? null;
        if (! is_array($columns) || $columns === []) {
            return [];
        }

        if (array_is_list($columns)) {
            $out = [];
            foreach ($columns as $column) {
                if (is_array($column)) {
                    $out[] = $column;
                }
            }

            return $out;
        }

        $out = [];
        foreach ($columns as $column) {
            if (is_array($column)) {
                $out[] = $column;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return list<array<string, mixed>>
     */
    private function hydrateRelatedCollection(
        Model $model,
        string $fieldId,
        string $relationName,
        array $fieldDefinition,
        ?CompositionDebugLog $log,
    ): array {
        $valueKey = trim((string) ($fieldDefinition['relation_value'] ?? 'id'));
        if ($valueKey === '') {
            $valueKey = 'id';
        }

        $limit = $this->effectiveLimit($fieldDefinition);
        $collection = $model->getRelation($relationName);
        if ($collection === null) {
            $log?->add(sprintf(
                'Form field "%s": relation [%s] was not eager-loaded; loading now (avoid N+1 by declaring this relation in schema).',
                $fieldId,
                $relationName,
            ));
            $model->load($relationName);
            $collection = $model->getRelation($relationName);
        }

        if ($collection === null) {
            return [];
        }

        $rows = [];
        $n = 0;
        foreach ($collection as $related) {
            if ($n >= $limit) {
                break;
            }
            $n++;

            $row = $this->rowFromRelatedModel($related, $valueKey, $fieldDefinition);
            $pivot = $related->relationLoaded('pivot') ? $related->pivot : null;
            if ($pivot !== null && count($pivot->getAttributes()) > 0) {
                $attrs = $pivot->getAttributes();
                unset($attrs['id']);
                if ($attrs !== []) {
                    $row['pivot'] = $attrs;
                }
            }

            $this->hydrateNestedRelationColumnPayloads($related, $fieldDefinition, $row);

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return list<array<string, mixed>>
     */
    private function hydrateRelatedSingle(
        Model $model,
        string $fieldId,
        string $relationName,
        array $fieldDefinition,
        ?CompositionDebugLog $log,
    ): array {
        $valueKey = trim((string) ($fieldDefinition['relation_value'] ?? 'id'));
        if ($valueKey === '') {
            $valueKey = 'id';
        }

        $related = $model->getRelation($relationName);
        if ($related === null) {
            $log?->add(sprintf(
                'Form field "%s": relation [%s] was not eager-loaded; loading now (avoid N+1 by declaring this relation in schema).',
                $fieldId,
                $relationName,
            ));
            $model->load($relationName);
            $related = $model->getRelation($relationName);
        }

        if (! $related instanceof Model) {
            return [];
        }

        $row = $this->rowFromRelatedModel($related, $valueKey, $fieldDefinition);
        $this->hydrateNestedRelationColumnPayloads($related, $fieldDefinition, $row);

        return [$row];
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>
     */
    private function rowFromRelatedModel(
        Model $related,
        string $valueKey,
        array $fieldDefinition,
    ): array {
        $id = $related->getAttribute($valueKey);
        $row = [
            $valueKey => $id !== null ? (string) $id : '',
        ];
        if ($valueKey !== 'id' && $related->getKey() !== null) {
            $row['id'] = (string) $related->getKey();
        }

        foreach (self::columnIdsFromDefinition($fieldDefinition) as $columnId) {
            if ($columnId === $valueKey || $columnId === 'id') {
                continue;
            }
            $row[$columnId] = $related->getAttribute($columnId);
        }

        $labelKey = RelationFieldQuery::stringFromField(
            $fieldDefinition,
            'relation_name',
            'relationName',
        );
        if (
            $labelKey !== ''
            && $labelKey !== $valueKey
            && ! array_key_exists($labelKey, $row)
        ) {
            $row[$labelKey] = $related->getAttribute($labelKey);
        }

        return $row;
    }

    /**
     * Relation-type columns render from {@code row[relation]} (see {@code formatRelationCellDisplay}); hydrate nested models here.
     *
     * @param  array<string, mixed>  $fieldDefinition
     * @param  array<string, mixed>  $row
     */
    private function hydrateNestedRelationColumnPayloads(
        Model $related,
        array $fieldDefinition,
        array &$row,
    ): void {
        foreach (self::columnDefinitionsList($fieldDefinition) as $columnDef) {
            $type = isset($columnDef['type']) ? trim((string) $columnDef['type']) : '';
            if ($type !== 'relation') {
                continue;
            }

            $nestedRelation = isset($columnDef['relation']) ? trim((string) $columnDef['relation']) : '';
            $displayKey = isset($columnDef['relation_name'])
                ? trim((string) $columnDef['relation_name'])
                : (isset($columnDef['relationName']) ? trim((string) $columnDef['relationName']) : '');
            $identityKey = isset($columnDef['relation_value'])
                ? trim((string) $columnDef['relation_value'])
                : (isset($columnDef['relationValue']) ? trim((string) $columnDef['relationValue']) : '');

            if ($nestedRelation === '' || $displayKey === '') {
                continue;
            }

            if ($identityKey === '') {
                $identityKey = 'id';
            }

            if (! method_exists($related, $nestedRelation)) {
                continue;
            }

            $related->loadMissing($nestedRelation);
            $nestedModel = $related->{$nestedRelation};
            if (! $nestedModel instanceof Model) {
                $row[$nestedRelation] = null;

                continue;
            }

            $identityVal = $nestedModel->getAttribute($identityKey);
            $row[$nestedRelation] = [
                $identityKey => $identityVal !== null ? (string) $identityVal : '',
                $displayKey => $nestedModel->getAttribute($displayKey),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function effectiveLimit(array $fieldDefinition): int
    {
        $yamlLimit = $fieldDefinition['limit'] ?? null;
        $fromYaml = is_int($yamlLimit) ? max(1, $yamlLimit) : null;

        $default = (int) config('flatpack.forms.relation_table_default_limit', 100);
        $hard = (int) config('flatpack.forms.relation_table_hard_max_rows', 500);
        if ($hard < 1) {
            $hard = 500;
        }

        $cap = $fromYaml ?? $default;
        if (config('flatpack.forms.enforce_relation_table_limit', true) !== true) {
            return min($cap, $hard);
        }

        return min($cap, $hard);
    }
}
