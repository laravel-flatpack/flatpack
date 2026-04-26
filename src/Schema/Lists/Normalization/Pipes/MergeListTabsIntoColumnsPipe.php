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
                $mergedById[$id] = $item;
                $mergedOrder[] = $id;
            }
        };

        $rootColumns = $state->schema['columns'] ?? null;
        $appendColumns($rootColumns);

        /** @var list<array{id: string, label: string, icon?: string, column_ids: list<string>}> $tabPanels */
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
                            $mergedById[$id] = $item;
                            $mergedOrder[] = $id;
                        }
                    }
                }
            }

            $entry = [
                'id' => $tabIdStr,
                'label' => $label,
                'column_ids' => $columnIdsOrdered,
            ];
            if ($icon !== null) {
                $entry['icon'] = $icon;
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
}
