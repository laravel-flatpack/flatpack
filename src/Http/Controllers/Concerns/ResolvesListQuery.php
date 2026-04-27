<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Facades\Flatpack;
use Illuminate\Http\Request;

/**
 * Shared list query extraction and normalization.
 */
trait ResolvesListQuery
{
    /**
     * @return array{
     *     page: int,
     *     perPage: int,
     *     searchTerm: string,
     *     filters: array<array-key, mixed>,
     *     tab: string,
     *     sortBy: string,
     *     sortDirection: string
     * }
     */
    private function listQueryFromRequest(Request $request): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $maxPerPage = Flatpack::maxListPerPage();
        $perPage = (int) $request->query('per_page', Flatpack::defaultListPerPage());
        $perPage = max(1, min($maxPerPage, $perPage));
        $searchTerm = trim((string) $request->query('search', ''));
        $filters = $request->query('filters', []);
        $filters = is_array($filters) ? $filters : [];
        $tab = trim((string) $request->query('tab', ''));
        $sortBy = trim((string) $request->query('sort_by', ''));
        $sortDirection = mb_strtolower(trim((string) $request->query('sort_direction', '')));

        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        return [
            'page' => $page,
            'perPage' => $perPage,
            'searchTerm' => $searchTerm,
            'filters' => $filters,
            'tab' => $tab,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
        ];
    }
}
