<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Schema\Lists\ListRecordsLoader;

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
     *     sortBy: string,
     *     sortDirection: string
     * }  $query
     * @return array<string, mixed>
     */
    private function loadRecordsForList(?string $modelClass, ?array $schema, array $query): array
    {
        return $this->listRecordsLoader()->load(
            $modelClass,
            $schema,
            $query['page'],
            $query['perPage'],
            $query['searchTerm'],
            $query['filters'],
            $query['sortBy'],
            $query['sortDirection'],
        );
    }

    private function listRecordsLoader(): ListRecordsLoader
    {
        return app(ListRecordsLoader::class);
    }
}
