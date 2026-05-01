<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

use Flatpack\Schema\Lists\ListSchemaNormalizer;
use Flatpack\Schema\Lists\NormalizedListSchema;

/**
 * Runs every list composition through {@see ListSchemaNormalizer} before tab resolution.
 */
final readonly class ActiveTabResolver
{
    public function __construct(
        private ListSchemaNormalizer $listSchemaNormalizer,
    ) {}

    /**
     * @param  array<string, mixed>|null  $schema
     */
    public function resolveWithSchema(?array $schema, string $requestedTabId): TabResolutionResult
    {
        $normalizedDto = $this->normalizeSchema($schema);
        $normalizedArray = $normalizedDto?->toArray();
        $activeTab = $this->resolveActiveTab($normalizedArray, $requestedTabId);
        $effectiveArray = $this->schemaForTab($normalizedArray, $activeTab);
        $scope = trim((string) ($activeTab['scope'] ?? ''));

        return new TabResolutionResult(
            activeTab: $activeTab,
            effectiveSchema: $effectiveArray !== null ? new NormalizedListSchema($effectiveArray) : null,
            scope: $scope !== '' ? $scope : null,
        );
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array{id: string, scope?: string, reorderable?: bool|string, reorderableColumn?: string, row_click?: string, columns?: mixed, filters?: mixed, bulk_actions?: mixed, default_sort?: array{key: string, direction: 'asc'|'desc'}, pagination?: bool}|null
     */
    public function resolve(?array $schema, string $requestedTabId): ?array
    {
        $normalizedDto = $this->normalizeSchema($schema);

        return $this->resolveActiveTab($normalizedDto?->toArray(), $requestedTabId);
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array{id: string, scope?: string, reorderable?: bool|string, reorderableColumn?: string, row_click?: string, columns?: mixed, filters?: mixed, bulk_actions?: mixed, default_sort?: array{key: string, direction: 'asc'|'desc'}, pagination?: bool}|null  $activeTab
     * @return array<string, mixed>|null
     */
    public function schemaForTab(?array $schema, ?array $activeTab): ?array
    {
        if ($schema === null || $activeTab === null) {
            return $schema;
        }
        $out = $schema;
        $hasScope = isset($activeTab['scope']) && trim((string) $activeTab['scope']) !== '';
        $tabColumnIds = $activeTab['column_ids'] ?? null;
        if (is_array($tabColumnIds)) {
            $resolvedColumns = $this->columnsForTabIds($schema, $tabColumnIds);
            if ($resolvedColumns !== []) {
                $out['columns'] = $resolvedColumns;
            }
        } else {
            $tabColumns = $activeTab['columns'] ?? null;
            if (is_array($tabColumns) && $tabColumns !== []) {
                $out['columns'] = $tabColumns;
            }
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
        if (isset($activeTab['default_sort']) && is_array($activeTab['default_sort'])) {
            $out['default_sort'] = $activeTab['default_sort'];
        }
        if (isset($activeTab['pagination']) && is_bool($activeTab['pagination'])) {
            $out['pagination'] = $activeTab['pagination'];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    private function normalizeSchema(?array $schema): ?NormalizedListSchema
    {
        if ($schema === null) {
            return null;
        }

        return $this->listSchemaNormalizer->normalizedListSchema($schema);
    }

    /**
     * @param  array<string, mixed>|null  $normalizedSchema
     * @return array{id: string, scope?: string, reorderable?: bool|string, reorderableColumn?: string, row_click?: string, columns?: mixed, filters?: mixed, bulk_actions?: mixed, default_sort?: array{key: string, direction: 'asc'|'desc'}, pagination?: bool, column_ids?: list<string>}|null
     */
    private function resolveActiveTab(?array $normalizedSchema, string $requestedTabId): ?array
    {
        $tabPanels = $normalizedSchema['tab_panels'] ?? null;
        if (! is_array($tabPanels) || $tabPanels === []) {
            return null;
        }

        /** @var list<array{id: string, scope?: string, reorderable?: bool|string, reorderableColumn?: string, row_click?: string, columns?: mixed, filters?: mixed, bulk_actions?: mixed, default_sort?: array{key: string, direction: 'asc'|'desc'}, pagination?: bool, column_ids?: list<string>}> $panels */
        $panels = [];
        foreach ($tabPanels as $panel) {
            if (! is_array($panel)) {
                continue;
            }
            $id = trim((string) ($panel['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $panels[] = $panel;
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
     * @param  array<string, mixed>  $schema
     * @param  list<string>  $columnIds
     * @return list<array<string, mixed>>
     */
    private function columnsForTabIds(array $schema, array $columnIds): array
    {
        $columns = $schema['columns'] ?? null;
        if (! is_array($columns) || $columns === []) {
            return [];
        }

        $byId = [];
        foreach ($columns as $column) {
            if (! is_array($column)) {
                continue;
            }
            $id = trim((string) ($column['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $byId[$id] = $column;
        }

        $resolved = [];
        foreach ($columnIds as $columnId) {
            $id = trim((string) $columnId);
            if ($id === '' || ! isset($byId[$id])) {
                continue;
            }
            $resolved[] = $byId[$id];
        }

        return $resolved;
    }
}
