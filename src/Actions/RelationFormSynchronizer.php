<?php

declare(strict_types=1);

namespace Flatpack\Actions;

use Flatpack\Schema\CompositionTabsMerge;
use Flatpack\Schema\Forms\FormFieldType;
use Flatpack\Services\Forms\FormRelationValuesHydrator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Applies {@code RelationRow[]} payloads from form {@code values} to Eloquent relations after the parent model is saved.
 */
final class RelationFormSynchronizer
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $values
     */
    public function sync(Model $model, ?array $schema, array $values): void
    {
        if ($schema === null) {
            return;
        }

        $schema = CompositionTabsMerge::form($schema) ?? $schema;

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return;
        }

        foreach ($fields as $yamlKey => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }

            $fieldId = trim((string) ($fieldDefinition['id'] ?? $yamlKey));
            if ($fieldId === '') {
                continue;
            }

            if (! FormFieldType::shouldDeferToRelationSync($fieldDefinition)) {
                continue;
            }

            if (! array_key_exists($fieldId, $values)) {
                continue;
            }

            $payload = $values[$fieldId];
            $relationName = trim((string) ($fieldDefinition['relation'] ?? ''));
            if ($relationName === '' || ! method_exists($model, $relationName)) {
                continue;
            }

            $relation = $model->{$relationName}();
            if ($this->isFileUploadRelationField($fieldDefinition)) {
                $this->syncFileUploadRelation($relation, $payload, $fieldDefinition);

                continue;
            }

            if ($relation instanceof BelongsToMany) {
                $this->syncBelongsToMany($relation, $payload, $fieldDefinition);

                continue;
            }

            if ($relation instanceof HasMany || $relation instanceof MorphMany) {
                $this->syncHasManyOrMorphMany($relation, $payload, $fieldDefinition);

                continue;
            }

            if ($relation instanceof HasOne) {
                $this->syncHasOne($relation, $payload, $fieldDefinition);

                continue;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function syncBelongsToMany(BelongsToMany $relation, mixed $payload, array $fieldDefinition): void
    {
        $rows = is_array($payload) ? $payload : [];
        $valueKey = trim((string) ($fieldDefinition['relation_value'] ?? 'id'));
        if ($valueKey === '') {
            $valueKey = 'id';
        }

        /** @var array<string, array<string, mixed>> $sync */
        $sync = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rawId = $row[$valueKey] ?? $row['id'] ?? null;
            if ($rawId === null || $rawId === '') {
                continue;
            }

            $id = (string) $rawId;
            $pivotRow = $row['pivot'] ?? null;
            $pivot = is_array($pivotRow) ? $pivotRow : [];
            unset($pivot['id']);

            $sync[$id] = $pivot;
        }

        $relation->sync($sync);
    }

    /**
     * Creates, updates, and deletes child models for HasMany / MorphMany inline tables (RelationRow payload).
     *
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function syncHasManyOrMorphMany(
        HasMany|MorphMany $relation,
        mixed $payload,
        array $fieldDefinition,
    ): void {
        $rows = is_array($payload) ? $payload : [];
        $valueKey = trim((string) ($fieldDefinition['relation_value'] ?? 'id'));
        if ($valueKey === '') {
            $valueKey = 'id';
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Model> $existing */
        $existing = $relation->get();

        /** @var \Illuminate\Support\Collection<string, Model> */
        $keyed = $existing->keyBy(fn (Model $m): string => (string) $m->getAttribute($valueKey));

        $incomingIds = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rawId = $row[$valueKey] ?? $row['id'] ?? null;
            if ($rawId === null || $rawId === '') {
                continue;
            }
            $incomingIds[(string) $rawId] = true;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $attrs = $this->attributesForHasManyChildRow($row, $fieldDefinition);
            $rawId = $row[$valueKey] ?? $row['id'] ?? null;

            if ($rawId === null || $rawId === '') {
                $relation->create($attrs);

                continue;
            }

            $id = (string) $rawId;
            $child = $keyed->get($id);
            if ($child !== null) {
                $child->fill($attrs);
                $child->save();
            }
        }

        foreach ($keyed as $valueId => $child) {
            if ($valueId === '') {
                continue;
            }
            if (! isset($incomingIds[$valueId])) {
                $child->delete();
            }
        }
    }

    /**
     * Creates, updates, or deletes the child model for HasOne inline relation payloads.
     *
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function syncHasOne(HasOne $relation, mixed $payload, array $fieldDefinition): void
    {
        $row = $this->rowFromSingleRelationPayload($payload);
        $existing = $relation->first();

        if ($row === null) {
            if ($existing !== null) {
                $existing->delete();
            }

            return;
        }

        $attrs = $this->attributesForHasManyChildRow($row, $fieldDefinition);
        if ($existing !== null) {
            $existing->fill($attrs);
            $existing->save();

            return;
        }

        $relation->create($attrs);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>
     */
    private function attributesForHasManyChildRow(array $row, array $fieldDefinition): array
    {
        $allowed = [];
        foreach (FormRelationValuesHydrator::columnIdsFromDefinition($fieldDefinition) as $columnId) {
            $allowed[$columnId] = true;
        }

        $out = [];
        foreach ($row as $key => $value) {
            if ($key === 'pivot') {
                continue;
            }
            if (! isset($allowed[$key])) {
                continue;
            }
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rowFromSingleRelationPayload(mixed $payload): ?array
    {
        if (! is_array($payload)) {
            return null;
        }

        if (array_is_list($payload)) {
            if ($payload === []) {
                return null;
            }
            $first = $payload[0];

            return is_array($first) ? $first : null;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function isFileUploadRelationField(array $fieldDefinition): bool
    {
        return FormFieldType::normalizeYamlType(trim((string) ($fieldDefinition['type'] ?? ''))) === 'file-upload';
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function syncFileUploadRelation(
        mixed $relation,
        mixed $payload,
        array $fieldDefinition,
    ): void {
        $rows = $this->uploadedFileRows($payload, $fieldDefinition);
        if ($relation instanceof HasMany || $relation instanceof MorphMany) {
            $relation->get()->each->delete();
            foreach ($rows as $row) {
                $relation->create($row);
            }

            return;
        }

        if ($relation instanceof HasOne) {
            $existing = $relation->first();
            if ($rows === []) {
                $existing?->delete();

                return;
            }

            $first = $rows[0];
            if ($existing !== null) {
                $existing->fill($first);
                $existing->save();

                return;
            }

            $relation->create($first);
        }
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return list<array<string, mixed>>
     */
    private function uploadedFileRows(mixed $payload, array $fieldDefinition): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $items = array_is_list($payload) ? $payload : [$payload];
        $collection = trim((string) ($fieldDefinition['collection'] ?? ''));
        $rows = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $path = trim((string) ($item['path'] ?? ''));
            if ($path === '') {
                continue;
            }

            $row = [
                'disk' => trim((string) ($item['disk'] ?? '')),
                'path' => $path,
                'url' => trim((string) ($item['url'] ?? $path)),
                'name' => trim((string) ($item['name'] ?? '')),
                'mime_type' => trim((string) ($item['mime_type'] ?? '')),
                'size' => (int) ($item['size'] ?? 0),
                'collection' => trim((string) ($item['collection'] ?? $collection)),
            ];

            $rows[] = array_filter(
                $row,
                static fn (mixed $value): bool => $value !== '',
            );
        }

        return $rows;
    }
}
