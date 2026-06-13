<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

use Flatpack\Facades\Flatpack;
use Flatpack\Schema\Forms\FormCompositionMergeForPersistence;
use Flatpack\Schema\Lists\FilterDefinition;
use Flatpack\Schema\Lists\FilterProcessor;
use Flatpack\Schema\Lists\Normalization\ReorderColumnResolver;
use Flatpack\Schema\Lists\RelationDefinition;
use Flatpack\Schema\Lists\RelationSerializer;
use Flatpack\Schema\Lists\SchemaInspector;
use Flatpack\Schema\Lists\SearchApplier;
use Flatpack\Schema\Lists\SortingProcessor;
use Flatpack\Services\Lists\Dto\ListQueryParams;
use Flatpack\Services\Uploads\FileUploadBrowserUrl;
use Illuminate\Database\Eloquent\Builder;
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
    public function __construct(
        private FileUploadBrowserUrl $fileUploadBrowserUrl,
    ) {}

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
        ListQueryParams $params,
        ?array $formSchema = null,
    ): array {
        $page = $params->page;
        $perPage = $params->perPage;
        $search = $params->search;
        $filters = $params->filters;
        $sortBy = $params->sortBy;
        $sortDirection = $params->sortDirection;
        $scope = $params->scope;

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

        $built = $this->buildQuery(
            modelClass: $modelClass,
            schema: $schema,
            columnKeys: $columnKeys,
            search: $search,
            filters: $filters,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            scope: $scope,
            filterDefinitions: $filterDefinitions,
        );

        $paginatorPayload = $this->paginateAndSerialize(
            query: $built['query'],
            columnKeys: $built['columnKeys'],
            relationDefs: $built['relationDefs'],
            perPage: $perPage,
            page: $page,
        );

        $mergedForm = $formSchema !== null
            ? FormCompositionMergeForPersistence::merge($formSchema, null)
            : null;
        $records = $this->enrichImageColumnBrowseUrls(
            $paginatorPayload['records'],
            $schema,
            $mergedForm,
        );

        return [
            'records' => $records,
            'pagination' => $paginatorPayload['pagination'],
            'filters' => $serializedFilterDefinitions,
            'filter_values' => $built['normalizedFilterValues'],
            'sorting' => $built['sorting'],
        ];
    }

    /**
     * @param  list<string>  $columnKeys
     * @param  list<FilterDefinition>  $filterDefinitions
     * @param  array<string, mixed>  $filters
     * @return array{
     *     query: Builder<Model>,
     *     columnKeys: list<string>,
     *     relationDefs: list<RelationDefinition>,
     *     normalizedFilterValues: array<string, string|list<string>|null>,
     *     sorting: array{sort_by: string|null, sort_direction: 'asc'|'desc'|null},
     * }
     */
    private function buildQuery(
        string $modelClass,
        ?array $schema,
        array $columnKeys,
        ?string $search,
        array $filters,
        ?string $sortBy,
        string $sortDirection,
        ?string $scope,
        array $filterDefinitions,
    ): array {
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
        $this->applyTabScope($query, $model, $scope);

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
        FilterProcessor::applyToQuery($query, $filterDefinitions, $normalizedFilterValues, $schema);
        [$defaultSortBy, $defaultSortDirection, $allowDefaultSortOutsideSortableColumns] = $this->defaultSortFromSchema($schema);
        $sortableColumns = SchemaInspector::sortableColumnIds($schema);
        if (
            $allowDefaultSortOutsideSortableColumns
            && $defaultSortBy !== null
            && ! in_array($defaultSortBy, $sortableColumns, true)
        ) {
            $sortableColumns[] = $defaultSortBy;
        }
        if (
            $defaultSortBy !== null
            && ! $allowDefaultSortOutsideSortableColumns
            && ! in_array($defaultSortBy, $sortableColumns, true)
        ) {
            $defaultSortBy = null;
            $defaultSortDirection = 'desc';
        }
        $normalizedSorting = SortingProcessor::normalizeAndApply(
            $query,
            $sortBy,
            $sortDirection,
            $sortableColumns,
            $model->getKeyName(),
            $model->getQualifiedKeyName(),
            $defaultSortBy,
            $defaultSortDirection,
        );

        return [
            'query' => $query,
            'columnKeys' => $columnKeys,
            'relationDefs' => $relationDefs,
            'normalizedFilterValues' => $normalizedFilterValues,
            'sorting' => $normalizedSorting,
        ];
    }

    /**
     * @param  Builder<Model>  $query
     * @param  list<string>  $columnKeys
     * @param  list<RelationDefinition>  $relationDefs
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
    private function paginateAndSerialize(
        Builder $query,
        array $columnKeys,
        array $relationDefs,
        int $perPage,
        int $page,
    ): array {
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
        ];
    }

    private function applyTabScope(Builder $query, Model $model, ?string $scope): void
    {
        $scopeName = trim((string) $scope);
        if ($scopeName === '') {
            return;
        }

        $scopeMethod = 'scope' . ucfirst($scopeName);
        if (! method_exists($model, $scopeMethod)) {
            return;
        }
        $query->{$scopeName}();
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array{0: string|null, 1: 'asc'|'desc', 2: bool}
     */
    private function defaultSortFromSchema(?array $schema): array
    {
        if (! is_array($schema)) {
            return [null, 'desc', false];
        }

        $resolvedReorderableColumn = ReorderColumnResolver::reorderColumnFromSchema($schema);

        $defaultSort = $schema['default_sort'] ?? null;
        if (is_array($defaultSort)) {
            $key = trim((string) ($defaultSort['key'] ?? ''));
            $direction = trim((string) ($defaultSort['direction'] ?? ''));
            if ($key !== '' && in_array($direction, ['asc', 'desc'], true)) {
                $allowOutsideSortableColumns =
                    $resolvedReorderableColumn !== null && $resolvedReorderableColumn === $key;

                return [$key, $direction, $allowOutsideSortableColumns];
            }
        }

        if ($resolvedReorderableColumn !== null) {
            return [$resolvedReorderableColumn, 'asc', true];
        }

        return [null, 'desc', false];
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

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  array<string, mixed>|null  $listSchema
     * @param  array<string, mixed>|null  $mergedFormSchema
     * @return list<array<string, mixed>>
     */
    private function enrichImageColumnBrowseUrls(
        array $records,
        ?array $listSchema,
        ?array $mergedFormSchema,
    ): array {
        $map = SchemaInspector::imageColumnFileUploadDefinitions(
            $listSchema,
            $mergedFormSchema,
        );
        if ($map === []) {
            return $records;
        }

        $browser = $this->fileUploadBrowserUrl;

        return array_map(
            function (array $row) use ($map, $browser): array {
                foreach ($map as $attr => $fieldDefinition) {
                    if (! array_key_exists($attr, $row)) {
                        continue;
                    }
                    $row[$attr] = $this->resolveListImageCellBrowseValue(
                        $browser,
                        $fieldDefinition,
                        $row[$attr],
                    );
                }

                return $row;
            },
            $records,
        );
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function resolveListImageCellBrowseValue(
        FileUploadBrowserUrl $browser,
        array $fieldDefinition,
        mixed $raw,
    ): mixed {
        if ($raw === null) {
            return null;
        }

        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === '') {
                return $raw;
            }

            if (preg_match('#^https?://#i', $trimmed)) {
                return $raw;
            }

            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return $this->resolveListImageCellBrowseValue($browser, $fieldDefinition, $decoded);
            }

            return $browser->resolveBrowseUrlForField($fieldDefinition, $trimmed);
        }

        if (is_array($raw)) {
            $disk = trim((string) ($raw['disk'] ?? ''));
            $path = trim((string) ($raw['path'] ?? ''));

            if ($disk !== '' && $path !== '') {
                return $browser->ensureFragmentBrowseUrl($raw, $fieldDefinition);
            }
        }

        return $raw;
    }
}
