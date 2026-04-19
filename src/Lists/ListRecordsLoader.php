<?php

declare(strict_types=1);

namespace Flatpack\Lists;

use Flatpack\Facades\Flatpack;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

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
     *     filters: list<array{
     *         id: string,
     *         label: string,
     *         placeholder?: string,
     *         type: 'select'|'date',
     *         multiple: bool,
     *         mode?: 'exact'|'from',
     *         options?: list<array{
     *             value: string,
     *             label: string,
     *             status?: 'success'|'pending'|'warning'|'error'|'info',
     *             icon?: string,
     *         }>,
     *     }>,
     *     filter_values: array<string, string|list<string>|null>,
     *     sorting: array{sort_by: string|null, sort_direction: 'asc'|'desc'|null},
     * }
     */
    public function load(
        ?string $modelClass,
        ?array $schema,
        int $page = 1,
        ?int $perPage = null,
        ?string $search = null,
        array $filters = [],
        ?string $sortBy = null,
        string $sortDirection = 'desc',
    ): array {
        $perPage ??= Flatpack::defaultListPerPage();
        $maxPerPage = Flatpack::maxListPerPage();
        $perPage = max(1, min($maxPerPage, $perPage));
        $page = max(1, $page);

        $filterDefinitions = SchemaInspector::filterDefinitions($schema);
        $serializedFilterDefinitions = array_map(
            static fn (FilterDefinition $definition): array => $definition->toArray(),
            $filterDefinitions,
        );
        $empty = [
            'records' => [],
            'pagination' => $this->emptyPagination($page, $perPage),
            'filters' => $serializedFilterDefinitions,
            'filter_values' => [],
            'sorting' => ['sort_by' => null, 'sort_direction' => null],
        ];

        if ($modelClass === null || $modelClass === '' || ! class_exists($modelClass)) {
            return $empty;
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            return $empty;
        }

        $columnKeys = SchemaInspector::columnKeys($schema);
        if ($columnKeys === []) {
            return $empty;
        }

        $relationDefs = SchemaInspector::relationColumnDefinitions($schema);
        $eagerRelations = SchemaInspector::uniqueRelationNames($relationDefs);

        $model = new $modelClass();
        $keyName = $model->getKeyName();

        if (! in_array($keyName, $columnKeys, true)) {
            $columnKeys = array_merge([$keyName], $columnKeys);
        }

        $query = $model
            ->newQuery()
            ->select($columnKeys);

        if ($eagerRelations !== []) {
            $query->with($eagerRelations);
        }

        $searchTerm = trim((string) $search);
        if ($searchTerm !== '') {
            SearchApplier::apply(
                $query,
                SchemaInspector::searchableColumnDefinitions($schema),
                $searchTerm,
            );
        }
        $normalizedFilterValues = FilterProcessor::normalizeValues(
            $filterDefinitions,
            $filters,
        );
        FilterProcessor::applyToQuery($query, $filterDefinitions, $normalizedFilterValues);
        $normalizedSorting = SortingProcessor::normalize(
            $sortBy,
            $sortDirection,
            SchemaInspector::sortableColumnIds($schema),
            $model->getKeyName(),
        );
        SortingProcessor::applyToQuery(
            $query,
            $normalizedSorting,
            $model->getKeyName(),
            $model->getQualifiedKeyName(),
        );

        /** @var LengthAwarePaginator<int, Model> $paginator */
        $paginator = $query->paginate($perPage, $columnKeys, 'page', $page);

        $rows = array_map(
            fn (Model $row): array => $this->serializeRow($row, $columnKeys, $relationDefs),
            $paginator->items(),
        );

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
            'filters' => $serializedFilterDefinitions,
            'filter_values' => $normalizedFilterValues,
            'sorting' => $normalizedSorting,
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
     * @param  list<string>  $columnKeys
     * @param  list<RelationDefinition>  $relationDefs
     * @return array<string, mixed>
     */
    private function serializeRow(Model $row, array $columnKeys, array $relationDefs): array
    {
        $baseColumns = $row->only($columnKeys);

        return array_reduce(
            $relationDefs,
            function (array $acc, RelationDefinition $def) use ($row): array {
                $relName = $def->relation;
                if (! $row->relationLoaded($relName)) {
                    $acc[$relName] = null;

                    return $acc;
                }

                $related = $row->getRelation($relName);
                $acc[$relName] = RelationSerializer::serializePayload(
                    $related,
                    $def->relationName,
                    $def->relationValue,
                );

                return $acc;
            },
            $baseColumns,
        );
    }
}
