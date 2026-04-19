<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists;

use Illuminate\Database\Eloquent\Builder;

final class SortingProcessor
{
    /**
     * @param  list<string>  $sortableColumns
     * @return array{sort_by: string|null, sort_direction: 'asc'|'desc'|null}
     */
    public static function normalize(
        ?string $sortBy,
        string $sortDirection,
        array $sortableColumns,
        string $modelKeyName,
    ): array {
        $direction = mb_strtolower(trim($sortDirection)) === 'asc' ? 'asc' : 'desc';
        $requestedSortBy = trim((string) $sortBy);
        if ($requestedSortBy === '') {
            return [
                'sort_by' => $modelKeyName,
                'sort_direction' => 'desc',
            ];
        }

        if (! in_array($requestedSortBy, $sortableColumns, true)) {
            return [
                'sort_by' => $modelKeyName,
                'sort_direction' => 'desc',
            ];
        }

        return [
            'sort_by' => $requestedSortBy,
            'sort_direction' => $direction,
        ];
    }

    /**
     * @param  array{sort_by: string|null, sort_direction: 'asc'|'desc'|null}  $sorting
     */
    public static function applyToQuery(
        Builder $query,
        array $sorting,
        string $modelKeyName,
        string $qualifiedModelKeyName,
    ): void {
        $sortBy = $sorting['sort_by'];
        $sortDirection = $sorting['sort_direction'] ?? 'desc';
        if ($sortBy === null) {
            return;
        }
        $column = $sortBy === $modelKeyName
            ? $qualifiedModelKeyName
            : $sortBy;
        $query->orderBy($column, $sortDirection);
    }
}
