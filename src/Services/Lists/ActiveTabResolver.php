<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

/**
 * Resolves which list tab is active and derives the effective list schema (columns, filters, etc.).
 */
final readonly class ActiveTabResolver
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @return array{
     *     activeTab: array{id: string, scope?: string, reorderable?: bool|string, reorderableColumn?: string, row_click?: string, columns?: mixed, filters?: mixed, bulk_actions?: mixed}|null,
     *     effectiveSchema: array<string, mixed>|null,
     *     scope: string|null
     * }
     */
    public function resolveWithSchema(?array $schema, string $requestedTabId): array
    {
        $activeTab = $this->resolve($schema, $requestedTabId);
        $effectiveSchema = $this->schemaForTab($schema, $activeTab);
        $scope = trim((string) ($activeTab['scope'] ?? ''));

        return [
            'activeTab' => $activeTab,
            'effectiveSchema' => $effectiveSchema,
            'scope' => $scope !== '' ? $scope : null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array{id: string, scope?: string, reorderable?: bool|string, reorderableColumn?: string, row_click?: string, columns?: mixed, filters?: mixed, bulk_actions?: mixed}|null
     */
    public function resolve(?array $schema, string $requestedTabId): ?array
    {
        $tabs = $schema['tabs'] ?? null;
        if (! is_array($tabs) || $tabs === []) {
            return null;
        }

        /** @var list<array{id: string, scope?: string, reorderable?: bool|string, reorderableColumn?: string, row_click?: string, columns?: mixed, filters?: mixed, bulk_actions?: mixed}> $panels */
        $panels = [];
        foreach ($tabs as $tabId => $panel) {
            $id = trim((string) $tabId);
            if ($id === '' || ! is_array($panel)) {
                continue;
            }
            $label = trim((string) ($panel['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $entry = ['id' => $id];
            $scope = trim((string) ($panel['scope'] ?? ''));
            if ($scope !== '') {
                $entry['scope'] = $scope;
            }
            if (is_bool($panel['reorderable'] ?? null)) {
                $entry['reorderable'] = $panel['reorderable'];
            } elseif (is_string($panel['reorderable'] ?? null) && trim($panel['reorderable']) !== '') {
                $entry['reorderable'] = trim($panel['reorderable']);
            }
            if (is_string($panel['reorderableColumn'] ?? null) && trim($panel['reorderableColumn']) !== '') {
                $entry['reorderableColumn'] = trim($panel['reorderableColumn']);
            }
            $rowClick = $panel['row_click'] ?? null;
            if (is_string($rowClick) && in_array($rowClick, ['none', 'edit_page', 'edit_modal', 'edit_drawer'], true)) {
                $entry['row_click'] = $rowClick;
            }
            if (isset($panel['columns']) && is_array($panel['columns'])) {
                $entry['columns'] = $panel['columns'];
            }
            if (isset($panel['filters']) && is_array($panel['filters'])) {
                $entry['filters'] = $panel['filters'];
            }
            $tabBulkActions = $panel['bulk_actions'] ?? $panel['bulkActions'] ?? null;
            if (is_array($tabBulkActions)) {
                $entry['bulk_actions'] = $tabBulkActions;
            }
            $panels[] = $entry;
        }

        if ($panels === []) {
            return null;
        }

        if ($requestedTabId !== '') {
            foreach ($panels as $entry) {
                if ($entry['id'] === $requestedTabId) {
                    return $entry;
                }
            }
        }

        return $panels[0];
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array{id: string, scope?: string, reorderable?: bool|string, reorderableColumn?: string, row_click?: string, columns?: mixed, filters?: mixed, bulk_actions?: mixed}|null  $activeTab
     * @return array<string, mixed>|null
     */
    public function schemaForTab(?array $schema, ?array $activeTab): ?array
    {
        if ($schema === null || $activeTab === null) {
            return $schema;
        }
        $out = $schema;
        $hasScope = isset($activeTab['scope']) && trim((string) $activeTab['scope']) !== '';
        $tabColumns = $activeTab['columns'] ?? null;
        if (is_array($tabColumns) && $tabColumns !== []) {
            $out['columns'] = $tabColumns;
        }
        $tabFilters = $activeTab['filters'] ?? null;
        if (is_array($tabFilters)) {
            $out['filters'] = $tabFilters;
        } elseif ($hasScope) {
            $out['filters'] = [];
        }
        $tabBulkActions = $activeTab['bulk_actions'] ?? null;
        if (is_array($tabBulkActions)) {
            $out['bulk_actions'] = $tabBulkActions;
        } elseif ($hasScope) {
            $out['bulk_actions'] = [];
        }
        if (isset($activeTab['reorderable']) && (is_bool($activeTab['reorderable']) || is_string($activeTab['reorderable']))) {
            $out['reorderable'] = $activeTab['reorderable'];
        }
        if (isset($activeTab['reorderableColumn']) && is_string($activeTab['reorderableColumn'])) {
            $out['reorderableColumn'] = $activeTab['reorderableColumn'];
        }
        if (isset($activeTab['row_click']) && is_string($activeTab['row_click'])) {
            $out['row_click'] = $activeTab['row_click'];
        }

        return $out;
    }
}
