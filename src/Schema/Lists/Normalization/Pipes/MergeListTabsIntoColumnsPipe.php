<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Lists\Normalization\ListSchemaPipelineState;

/**
 * Flattens {@code tabs.*.columns} into the top-level {@code columns} list (single column set for the table)
 * and replaces {@code tabs} with {@code tab_panels} layout metadata for the UI.
 */
final readonly class MergeListTabsIntoColumnsPipe
{
    public function handle(ListSchemaPipelineState $state, Closure $next): mixed
    {
        $this->applyReorderableColumn($state->schema);

        $tabs = $state->schema['tabs'] ?? null;
        if (! is_array($tabs) || $tabs === []) {
            return $next($state);
        }

        /** @var array<string, mixed> $mergedById */
        $mergedById = [];
        /** @var list<string> $mergedOrder */
        $mergedOrder = [];

        $appendColumns = function (mixed $columns) use (&$mergedById, &$mergedOrder, $state): void {
            if (! is_array($columns)) {
                return;
            }
            if ($columns === []) {
                return;
            }

            if (array_is_list($columns)) {
                foreach ($columns as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $id = trim((string) ($item['id'] ?? ''));
                    if ($id === '') {
                        continue;
                    }
                    if (isset($mergedById[$id]) && $state->log !== null) {
                        $state->log->add(sprintf(
                            'List tabs: column id "%s" is declared more than once (first declaration wins).',
                            $id,
                        ));

                        continue;
                    }
                    $mergedById[$id] = $item;
                    $mergedOrder[] = $id;
                }

                return;
            }

            foreach ($columns as $yamlKey => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $id = trim((string) ($item['id'] ?? $yamlKey));
                if ($id === '') {
                    continue;
                }
                if (isset($mergedById[$id]) && $state->log !== null) {
                    $state->log->add(sprintf(
                        'List tabs: column id "%s" is declared more than once (first declaration wins).',
                        $id,
                    ));

                    continue;
                }
                $mergedById[$id] = array_merge(['id' => $id], $item);
                $mergedOrder[] = $id;
            }
        };

        $rootColumns = $state->schema['columns'] ?? null;
        $appendColumns($rootColumns);

        /** @var list<string> $rootColumnIds */
        $rootColumnIds = $mergedOrder;

        /** @var list<array{id: string, label: string, icon?: string, scope?: string, reorderable?: bool|string, reorderableColumn?: string, row_click?: string, default_sort?: array{key: string, direction: 'asc'|'desc'}, pagination?: bool, columns?: mixed, filters?: mixed, bulk_actions?: mixed, column_ids: list<string>}> $tabPanels */
        $tabPanels = [];

        foreach ($tabs as $tabId => $panel) {
            $tabIdStr = trim((string) $tabId);
            if ($tabIdStr === '' || ! is_array($panel)) {
                continue;
            }

            $label = isset($panel['label']) && is_string($panel['label'])
                ? trim($panel['label'])
                : '';
            if ($label === '') {
                continue;
            }

            $icon = null;
            if (isset($panel['icon']) && is_string($panel['icon']) && trim($panel['icon']) !== '') {
                $icon = trim($panel['icon']);
            }
            $scope = null;
            if (isset($panel['scope']) && is_string($panel['scope']) && trim($panel['scope']) !== '') {
                $scope = trim($panel['scope']);
            }
            $reorderable = null;
            if (is_bool($panel['reorderable'] ?? null)) {
                $reorderable = $panel['reorderable'];
            } elseif (is_string($panel['reorderable'] ?? null) && trim($panel['reorderable']) !== '') {
                $reorderable = trim($panel['reorderable']);
            }
            $rowClick = null;
            $rowClickValue = $panel['row_click'] ?? null;
            if (is_string($rowClickValue) && in_array($rowClickValue, ['none', 'edit_page', 'edit_modal', 'edit_drawer'], true)) {
                $rowClick = $rowClickValue;
            }
            $defaultSort = null;
            $defaultSortRaw = $panel['default_sort'] ?? null;
            if (is_array($defaultSortRaw)) {
                $sortKey = trim((string) ($defaultSortRaw['key'] ?? ''));
                $sortDirection = trim((string) ($defaultSortRaw['direction'] ?? ''));
                if ($sortKey !== '' && in_array($sortDirection, ['asc', 'desc'], true)) {
                    $defaultSort = [
                        'key' => $sortKey,
                        'direction' => $sortDirection,
                    ];
                }
            }

            $tabColumns = $panel['columns'] ?? null;
            /** @var list<string> $columnIdsOrdered */
            $columnIdsOrdered = [];
            if (is_array($tabColumns)) {
                if (array_is_list($tabColumns)) {
                    foreach ($tabColumns as $item) {
                        if (! is_array($item)) {
                            continue;
                        }
                        $id = trim((string) ($item['id'] ?? ''));
                        if ($id === '') {
                            continue;
                        }
                        $columnIdsOrdered[] = $id;
                        if (! isset($mergedById[$id])) {
                            $mergedById[$id] = $item;
                            $mergedOrder[] = $id;
                        }
                    }
                } else {
                    foreach ($tabColumns as $yamlKey => $item) {
                        if (! is_array($item)) {
                            continue;
                        }
                        $id = trim((string) ($item['id'] ?? $yamlKey));
                        if ($id === '') {
                            continue;
                        }
                        $columnIdsOrdered[] = $id;
                        if (! isset($mergedById[$id])) {
                            $mergedById[$id] = array_merge(['id' => $id], $item);
                            $mergedOrder[] = $id;
                        }
                    }
                }
            } else {
                $columnIdsOrdered = $rootColumnIds;
            }

            $entry = [
                'id' => $tabIdStr,
                'label' => $label,
                'column_ids' => $columnIdsOrdered,
            ];
            if ($icon !== null) {
                $entry['icon'] = $icon;
            }
            if ($scope !== null) {
                $entry['scope'] = $scope;
            }
            if ($reorderable !== null) {
                $entry['reorderable'] = $reorderable;
                $resolvedTabColumn = $this->resolveReorderableColumn($reorderable);
                if ($resolvedTabColumn !== null) {
                    $entry['reorderableColumn'] = $resolvedTabColumn;
                }
            }
            if ($rowClick !== null) {
                $entry['row_click'] = $rowClick;
            }
            if ($defaultSort !== null) {
                $entry['default_sort'] = $defaultSort;
            }
            if (is_bool($panel['pagination'] ?? null)) {
                $entry['pagination'] = $panel['pagination'];
            }
            if (is_array($tabColumns) && $tabColumns !== []) {
                $entry['columns'] = $tabColumns;
            }
            if (isset($panel['filters']) && is_array($panel['filters'])) {
                $entry['filters'] = $panel['filters'];
            }
            $tabBulkActions = $panel['bulk_actions'] ?? $panel['bulkActions'] ?? null;
            if (is_array($tabBulkActions)) {
                $entry['bulk_actions'] = $tabBulkActions;
            }
            $tabPanels[] = $entry;
        }

        /** @var list<mixed> $mergedList */
        $mergedList = [];
        foreach ($mergedOrder as $id) {
            if (isset($mergedById[$id])) {
                $mergedList[] = $mergedById[$id];
            }
        }

        $state->schema['columns'] = $mergedList;
        $state->schema['tab_panels'] = $tabPanels;
        unset($state->schema['tabs']);

        return $next($state);
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function applyReorderableColumn(array &$schema): void
    {
        $resolved = $this->resolveReorderableColumn($schema['reorderable'] ?? null);
        if ($resolved !== null) {
            $schema['reorderableColumn'] = $resolved;
        }
    }

    private function resolveReorderableColumn(mixed $reorderable): ?string
    {
        if ($reorderable === true || $reorderable === 'true') {
            return 'sort_order';
        }
        if (is_string($reorderable) && trim($reorderable) !== '') {
            return trim($reorderable);
        }

        return null;
    }
}
