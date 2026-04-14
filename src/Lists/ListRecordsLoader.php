<?php

declare(strict_types=1);

namespace Flatpack\Lists;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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
    private const FILTER_TYPE_SELECT = 'select';

    private const FILTER_TYPE_DATE = 'date';

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
     *     filters: list<array{
     *         id: string,
     *         label: string,
     *         placeholder?: string,
     *         type: 'select'|'date',
     *         multiple: bool,
     *         mode?: 'exact'|'from',
     *         options?: list<array{value: string, label: string}>,
     *     }>,
     *     filter_values: array<string, string|list<string>|null>,
     * }
     */
    public function load(
        ?string $modelClass,
        ?array $schema,
        int $page = 1,
        ?int $perPage = null,
        ?string $search = null,
        array $filters = [],
    ): array {
        $perPage ??= (int) config('flatpack.list.per_page', 10);
        $maxPerPage = (int) config('flatpack.list.max_per_page', 100);
        $perPage = max(1, min($maxPerPage, $perPage));
        $page = max(1, $page);

        $empty = [
            'records' => [],
            'pagination' => $this->emptyPagination($page, $perPage),
            'filters' => $this->filterDefinitionsFromSchema($schema),
            'filter_values' => [],
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

        $searchTerm = trim((string) $search);
        if ($searchTerm !== '') {
            $this->applySearchToQuery($query, $schema, $searchTerm);
        }
        $filterDefinitions = $this->filterDefinitionsFromSchema($schema);
        $normalizedFilterValues = $this->normalizeFilterValues(
            $filterDefinitions,
            $filters,
        );
        $this->applyFiltersToQuery($query, $filterDefinitions, $normalizedFilterValues);

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
            'filters' => $filterDefinitions,
            'filter_values' => $normalizedFilterValues,
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
        return array_keys($this->normalizedColumnsById($schema));
    }

    /**
     * @return list<array{relation: string, relationName: string, relationValue: string}>
     */
    private function relationColumnDefinitionsFromSchema(?array $schema): array
    {
        $out = [];
        foreach ($this->normalizedColumnsById($schema) as $column) {
            $def = $this->parseRelationColumnDefinition($column);
            if ($def !== null) {
                $out[] = $def;
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

    private function applySearchToQuery(Builder $query, ?array $schema, string $searchTerm): void
    {
        $searchableDefs = $this->searchableColumnDefinitionsFromSchema($schema);
        if ($searchableDefs === []) {
            return;
        }

        $query->where(function (Builder $nested) use ($searchableDefs, $searchTerm): void {
            $hasCondition = false;
            foreach ($searchableDefs as $def) {
                if (($def['kind'] ?? '') === 'relation') {
                    $relation = (string) ($def['relation'] ?? '');
                    $relationName = (string) ($def['relationName'] ?? '');
                    if ($relation === '' || $relationName === '') {
                        continue;
                    }
                    if ($hasCondition) {
                        $nested->orWhereHas($relation, function (Builder $relationQuery) use ($relationName, $searchTerm): void {
                            $relationQuery->where($relationName, 'like', '%' . $searchTerm . '%');
                        });
                    } else {
                        $nested->whereHas($relation, function (Builder $relationQuery) use ($relationName, $searchTerm): void {
                            $relationQuery->where($relationName, 'like', '%' . $searchTerm . '%');
                        });
                    }
                    $hasCondition = true;

                    continue;
                }

                $column = (string) ($def['id'] ?? '');
                if ($column === '') {
                    continue;
                }
                if (! $hasCondition) {
                    $nested->where($column, 'like', '%' . $searchTerm . '%');
                } else {
                    $nested->orWhere($column, 'like', '%' . $searchTerm . '%');
                }
                $hasCondition = true;
            }
        });
    }

    /**
     * @return list<array{kind: 'column'|'relation', id?: string, relation?: string, relationName?: string}>
     */
    private function searchableColumnDefinitionsFromSchema(?array $schema): array
    {
        $searchableDefs = [];
        foreach ($this->normalizedColumnsById($schema) as $id => $column) {
            if (($column['searchable'] ?? false) !== true) {
                continue;
            }

            $type = isset($column['type']) ? trim((string) $column['type']) : '';
            if ($type === 'relation') {
                $relation = isset($column['relation']) ? trim((string) $column['relation']) : '';
                $relationName = $this->stringFromColumn(
                    $column,
                    'relation_name',
                    'relationName',
                );
                if ($relation === '' || $relationName === '') {
                    continue;
                }
                $searchableDefs[] = [
                    'kind' => 'relation',
                    'relation' => $relation,
                    'relationName' => $relationName,
                ];

                continue;
            }

            $searchableDefs[] = [
                'kind' => 'column',
                'id' => $id,
            ];
        }

        return $searchableDefs;
    }

    /**
     * @return list<array{
     *     id: string,
     *     label: string,
     *     placeholder?: string,
     *     type: 'select'|'date',
     *     multiple: bool,
     *     mode?: 'exact'|'from',
     *     options?: list<array{value: string, label: string}>,
     * }>
     */
    private function filterDefinitionsFromSchema(?array $schema): array
    {
        $filters = $schema['filters'] ?? null;
        if (! is_array($filters) || $filters === []) {
            return [];
        }
        $columnsById = $this->normalizedColumnsById($schema);

        $out = [];
        foreach ($filters as $filterId => $filterConfig) {
            $id = trim((string) $filterId);
            if ($id === '') {
                continue;
            }
            $column = $columnsById[$id] ?? null;
            if (! is_array($column)) {
                continue;
            }

            $columnType = isset($column['type']) ? trim((string) $column['type']) : 'text';
            if ($columnType === 'datetime') {
                $columnType = 'date';
            }

            $config = is_array($filterConfig) ? $filterConfig : [];
            $configuredType = isset($config['type']) ? trim((string) $config['type']) : '';
            $type = in_array($configuredType, [self::FILTER_TYPE_SELECT, self::FILTER_TYPE_DATE], true)
                ? $configuredType
                : $columnType;

            if ($type !== self::FILTER_TYPE_SELECT && $type !== self::FILTER_TYPE_DATE) {
                continue;
            }
            if ($type === self::FILTER_TYPE_SELECT && $columnType !== self::FILTER_TYPE_SELECT) {
                continue;
            }
            if ($type === self::FILTER_TYPE_DATE && $columnType !== self::FILTER_TYPE_DATE) {
                continue;
            }

            $label = isset($config['label']) && is_string($config['label'])
                ? trim($config['label'])
                : (isset($column['label']) ? trim((string) $column['label']) : $id);
            $placeholder = isset($config['placeholder']) && is_string($config['placeholder'])
                ? trim($config['placeholder'])
                : '';

            if ($type === self::FILTER_TYPE_SELECT) {
                $options = $this->normalizeSelectFilterOptions($column['options'] ?? null);
                if ($options === []) {
                    continue;
                }
                $out[] = [
                    'id' => $id,
                    'label' => $label !== '' ? $label : $id,
                    'placeholder' => $placeholder,
                    'type' => self::FILTER_TYPE_SELECT,
                    'multiple' => ($config['multiple'] ?? false) === true,
                    'options' => $options,
                ];

                continue;
            }

            $mode = (($config['mode'] ?? 'exact') === 'from') ? 'from' : 'exact';
            $out[] = [
                'id' => $id,
                'label' => $label !== '' ? $label : $id,
                'placeholder' => $placeholder,
                'type' => self::FILTER_TYPE_DATE,
                'multiple' => false,
                'mode' => $mode,
            ];
        }

        return $out;
    }

    /**
     * @param  mixed  $raw
     * @return list<array{value: string, label: string}>
     */
    private function normalizeSelectFilterOptions(mixed $raw): array
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
                $out[] = ['value' => $value, 'label' => $label];
            }

            return $out;
        }

        foreach ($raw as $value => $label) {
            if (! is_string($label)) {
                continue;
            }
            $val = trim((string) $value);
            $lab = trim($label);
            if ($val === '' || $lab === '') {
                continue;
            }
            $out[] = ['value' => $val, 'label' => $lab];
        }

        return $out;
    }

    /**
     * @param  list<array{
     *     id: string,
     *     label: string,
     *     placeholder?: string,
     *     type: 'select'|'date',
     *     multiple: bool,
     *     mode?: 'exact'|'from',
     *     options?: list<array{value: string, label: string}>,
     * }>  $definitions
     * @param  array<string, mixed>  $input
     * @return array<string, string|list<string>|null>
     */
    private function normalizeFilterValues(array $definitions, array $input): array
    {
        $out = [];
        foreach ($definitions as $def) {
            $id = $def['id'];
            $raw = $input[$id] ?? null;
            if ($raw === null) {
                $out[$id] = null;

                continue;
            }

            if ($def['type'] === self::FILTER_TYPE_SELECT) {
                $allowed = array_column($def['options'] ?? [], 'value');
                if (($def['multiple'] ?? false) === true) {
                    $values = is_array($raw) ? $raw : [$raw];
                    $normalized = [];
                    foreach ($values as $value) {
                        $v = trim((string) $value);
                        if ($v === '' || ! in_array($v, $allowed, true)) {
                            continue;
                        }
                        $normalized[] = $v;
                    }
                    $out[$id] = $normalized !== [] ? array_values(array_unique($normalized)) : null;

                    continue;
                }

                $value = trim((string) $raw);
                $out[$id] = ($value !== '' && in_array($value, $allowed, true))
                    ? $value
                    : null;

                continue;
            }

            $value = trim((string) $raw);
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $out[$id] = null;

                continue;
            }
            $out[$id] = $value;
        }

        return $out;
    }

    /**
     * @param  list<array{
     *     id: string,
     *     label: string,
     *     placeholder?: string,
     *     type: 'select'|'date',
     *     multiple: bool,
     *     mode?: 'exact'|'from',
     *     options?: list<array{value: string, label: string}>,
     * }>  $definitions
     * @param  array<string, string|list<string>|null>  $values
     */
    private function applyFiltersToQuery(Builder $query, array $definitions, array $values): void
    {
        foreach ($definitions as $def) {
            $id = $def['id'];
            $value = $values[$id] ?? null;
            if ($value === null) {
                continue;
            }

            if ($def['type'] === self::FILTER_TYPE_SELECT) {
                if (($def['multiple'] ?? false) === true && is_array($value)) {
                    if ($value !== []) {
                        $query->whereIn($id, $value);
                    }

                    continue;
                }
                if (is_string($value) && $value !== '') {
                    $query->where($id, $value);
                }

                continue;
            }

            if (! is_string($value) || $value === '') {
                continue;
            }
            if (($def['mode'] ?? 'exact') === 'from') {
                $query->whereDate($id, '>=', $value);
            } else {
                $query->whereDate($id, $value);
            }
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function normalizedColumnsById(?array $schema): array
    {
        $columns = $schema['columns'] ?? null;
        if (! is_array($columns) || $columns === []) {
            return [];
        }

        $out = [];
        if (array_is_list($columns)) {
            foreach ($columns as $column) {
                if (! is_array($column)) {
                    continue;
                }
                $id = isset($column['id']) ? trim((string) $column['id']) : '';
                if ($id === '') {
                    continue;
                }
                $out[$id] = $column;
            }

            return $out;
        }

        foreach ($columns as $key => $column) {
            if (! is_array($column)) {
                continue;
            }
            $id = isset($column['id']) ? trim((string) $column['id']) : trim((string) $key);
            if ($id === '') {
                continue;
            }
            $out[$id] = $column;
        }

        return $out;
    }
}
