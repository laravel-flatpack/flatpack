<?php

declare(strict_types=1);

namespace Flatpack\Lists;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Loads tabular rows for a Flatpack entity list from the configured Eloquent model.
 *
 * Relation columns (`type: relation`) are resolved with a single {@see Model::with()} eager load
 * per distinct relation name so listing rows does not trigger N+1 queries. Serialized relation
 * payloads are merged under the relation name (e.g. `category`) for the frontend to read.
 */
final readonly class ListRecordsLoader
{
    /**
     * @return array{
     *     records: list<array<string, mixed>>,
     *     pagination: array{
     *         current_page: int,
     *         last_page: int,
     *         per_page: int,
     *         total: int,
     *         from: int|null,
     *         to: int|null,
     *     },
     * }
     */
    public function load(
        ?string $modelClass,
        ?array $schema,
        int $page = 1,
        ?int $perPage = null,
    ): array {
        $perPage = $perPage ?? (int) config('flatpack.list.per_page', 10);
        $maxPerPage = (int) config('flatpack.list.max_per_page', 100);
        $perPage = max(1, min($maxPerPage, $perPage));
        $page = max(1, $page);

        $empty = [
            'records' => [],
            'pagination' => $this->emptyPagination($page, $perPage),
        ];

        if ($modelClass === null || $modelClass === '' || ! class_exists($modelClass)) {
            return $empty;
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            return $empty;
        }

        $columnKeys = $this->columnKeysFromListSchema($schema);
        if ($columnKeys === []) {
            return $empty;
        }

        $relationDefs = $this->relationColumnDefinitionsFromSchema($schema);
        $eagerRelations = $this->uniqueRelationNames($relationDefs);

        $model = new $modelClass();
        $keyName = $model->getKeyName();

        if (! in_array($keyName, $columnKeys, true)) {
            $columnKeys = array_merge([$keyName], $columnKeys);
        }

        $query = $model
            ->newQuery()
            ->select($columnKeys)
            ->orderByDesc($model->getQualifiedKeyName());

        if ($eagerRelations !== []) {
            $query->with($eagerRelations);
        }

        /** @var LengthAwarePaginator<int, Model> $paginator */
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $rows = [];
        foreach ($paginator->items() as $row) {
            $arr = $row->only($columnKeys);
            foreach ($relationDefs as $def) {
                $relName = $def['relation'];
                if (! $row->relationLoaded($relName)) {
                    $arr[$relName] = null;

                    continue;
                }
                $related = $row->getRelation($relName);
                $arr[$relName] = $this->serializeRelationPayload(
                    $related,
                    $def['relationName'],
                    $def['relationValue'],
                );
            }
            $rows[] = $arr;
        }

        return [
            'records' => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    /**
     * @return array{
     *     current_page: int,
     *     last_page: int,
     *     per_page: int,
     *     total: int,
     *     from: int|null,
     *     to: int|null,
     * }
     */
    private function emptyPagination(int $page, int $perPage): array
    {
        return [
            'current_page' => $page,
            'last_page' => 1,
            'per_page' => $perPage,
            'total' => 0,
            'from' => null,
            'to' => null,
        ];
    }

    /**
     * @return list<string>
     */
    private function columnKeysFromListSchema(?array $schema): array
    {
        $columns = $schema['columns'] ?? null;
        if ($columns === null || ! is_array($columns)) {
            return [];
        }

        if ($columns === []) {
            return [];
        }

        if (array_is_list($columns)) {
            $keys = [];
            foreach ($columns as $column) {
                if (! is_array($column)) {
                    continue;
                }
                if (! isset($column['id'])) {
                    continue;
                }
                $id = (string) $column['id'];
                if ($id !== '') {
                    $keys[] = $id;
                }
            }

            return $keys;
        }

        return array_keys($columns);
    }

    /**
     * @return list<array{relation: string, relationName: string, relationValue: string}>
     */
    private function relationColumnDefinitionsFromSchema(?array $schema): array
    {
        $columns = $schema['columns'] ?? null;
        if ($columns === null || ! is_array($columns)) {
            return [];
        }

        $out = [];
        if (array_is_list($columns)) {
            foreach ($columns as $column) {
                if (! is_array($column)) {
                    continue;
                }
                $id = isset($column['id']) ? (string) $column['id'] : '';
                if ($id === '') {
                    continue;
                }
                $def = $this->parseRelationColumnDefinition($column);
                if ($def !== null) {
                    $out[] = $def;
                }
            }
        } else {
            foreach ($columns as $key => $column) {
                if (! is_array($column)) {
                    continue;
                }
                $id = isset($column['id']) ? (string) $column['id'] : (string) $key;
                if ($id === '') {
                    continue;
                }
                $def = $this->parseRelationColumnDefinition($column);
                if ($def !== null) {
                    $out[] = $def;
                }
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $column
     * @return array{relation: string, relationName: string, relationValue: string}|null
     */
    private function parseRelationColumnDefinition(array $column): ?array
    {
        $type = isset($column['type']) ? (string) $column['type'] : '';
        if ($type !== 'relation') {
            return null;
        }

        $relation = isset($column['relation']) ? trim((string) $column['relation']) : '';
        $relationName = $this->stringFromColumn($column, 'relation_name', 'relationName');
        $relationValue = $this->stringFromColumn($column, 'relation_value', 'relationValue');

        if ($relation === '' || $relationName === '' || $relationValue === '') {
            return null;
        }

        return [
            'relation' => $relation,
            'relationName' => $relationName,
            'relationValue' => $relationValue,
        ];
    }

    /**
     * @param  array<string, mixed>  $column
     */
    private function stringFromColumn(array $column, string $snakeKey, string $camelKey): string
    {
        foreach ([$snakeKey, $camelKey] as $key) {
            if (isset($column[$key]) && is_string($column[$key])) {
                $s = trim($column[$key]);

                return $s;
            }
        }

        return '';
    }

    /**
     * @param  list<array{relation: string, relationName: string, relationValue: string}>  $defs
     * @return list<string>
     */
    private function uniqueRelationNames(array $defs): array
    {
        $names = [];
        foreach ($defs as $def) {
            $names[$def['relation']] = true;
        }

        return array_keys($names);
    }

    private function serializeRelationPayload(mixed $related, string $relationName, string $relationValue): mixed
    {
        if ($related === null) {
            return null;
        }

        if ($related instanceof Model) {
            return [
                $relationValue => $related->getAttribute($relationValue),
                $relationName => $related->getAttribute($relationName),
            ];
        }

        if ($related instanceof Collection) {
            return $related
                ->map(function (mixed $item) use ($relationName, $relationValue): ?array {
                    if (! $item instanceof Model) {
                        return null;
                    }

                    return [
                        $relationValue => $item->getAttribute($relationValue),
                        $relationName => $item->getAttribute($relationName),
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        return null;
    }
}
