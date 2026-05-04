<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Services\Lists\Dto\ListQueryParams;
use Flatpack\Services\Lists\ListRecordsLoader;

/**
 * Shared list record loading for list controllers.
 */
trait LoadsListRecords
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array{
     *     page: int,
     *     perPage: int,
     *     searchTerm: string,
     *     filters: array<array-key, mixed>,
     *     tab: string,
     *     sortBy: string,
     *     sortDirection: string
     * }  $query
     * @return array<string, mixed>
     */
    private function loadRecordsForList(
        ?string $modelClass,
        ?array $schema,
        array $query,
        ?string $scope = null,
        ?array $formSchema = null,
    ): array {
        return $this->listRecordsLoader()->load(
            $modelClass,
            $schema,
            new ListQueryParams(
                page: $query['page'],
                perPage: $query['perPage'],
                search: $query['searchTerm'],
                filters: $query['filters'],
                sortBy: $query['sortBy'],
                sortDirection: $query['sortDirection'],
                scope: $scope,
            ),
            $formSchema,
        );
    }

    private function listRecordsLoader(): ListRecordsLoader
    {
        return app(ListRecordsLoader::class);
    }
}
