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
        ?string $defaultSortBy = null,
        string $defaultSortDirection = 'desc',
    ): array {
        $direction = mb_strtolower(trim($sortDirection)) === 'asc' ? 'asc' : 'desc';
        $requestedSortBy = trim((string) $sortBy);
        if ($requestedSortBy === '') {
            $fallbackSortBy = trim((string) $defaultSortBy);
            $fallbackDirection = mb_strtolower(trim($defaultSortDirection)) === 'asc' ? 'asc' : 'desc';
            if ($fallbackSortBy !== '') {
                return [
                    'sort_by' => $fallbackSortBy,
                    'sort_direction' => $fallbackDirection,
                ];
            }

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
     * @param  list<string>  $sortableColumns
     * @return array{sort_by: string|null, sort_direction: 'asc'|'desc'|null}
     */
    public static function normalizeAndApply(
        Builder $query,
        ?string $sortBy,
        string $sortDirection,
        array $sortableColumns,
        string $modelKeyName,
        string $qualifiedModelKeyName,
        ?string $defaultSortBy = null,
        string $defaultSortDirection = 'desc',
    ): array {
        $sorting = self::normalize(
            $sortBy,
            $sortDirection,
            $sortableColumns,
            $modelKeyName,
            $defaultSortBy,
            $defaultSortDirection,
        );
        self::applyToQuery($query, $sorting, $modelKeyName, $qualifiedModelKeyName);

        return $sorting;
    }

    /**
     * @param  array{sort_by: string|null, sort_direction: 'asc'|'desc'|null}  $sorting
     */
    private static function applyToQuery(
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
        $grammar = $query->getQuery()->getGrammar();
        $direction = $sortDirection === 'asc' ? 'ASC' : 'DESC';
        $query->orderByRaw($grammar->wrap($column) . ' ' . $direction);
    }
}
