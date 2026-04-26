<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Forms\Normalization\FormSchemaPipelineState;

/**
 * Flattens {@code tabs.*.fields} into the top-level {@code fields} map (single values object for submit)
 * and replaces {@code tabs} with {@code tab_panels} layout metadata for the UI.
 *
 * When both top-level {@code fields} and {@code tabs} are present, root entries are copied first, then
 * tab entries are merged (same id: last declaration wins; a normalizer log entry may record the clash).
 */
final readonly class MergeFormTabsIntoFieldsPipe
{
    public function handle(FormSchemaPipelineState $state, Closure $next): mixed
    {
        $tabs = $state->schema['tabs'] ?? null;
        if (! is_array($tabs) || $tabs === []) {
            return $next($state);
        }

        /** @var array<string, mixed> $merged */
        $merged = [];
        $rootFields = $state->schema['fields'] ?? null;
        if (is_array($rootFields)) {
            foreach ($rootFields as $k => $v) {
                $merged[(string) $k] = $v;
            }
        }

        /** @var list<array{id: string, label: string, icon?: string, field_ids: list<string>}> $tabPanels */
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

            $tabFields = $panel['fields'] ?? null;
            /** @var list<string> $fieldIdsOrdered */
            $fieldIdsOrdered = [];
            if (is_array($tabFields)) {
                foreach ($tabFields as $yamlKey => $definition) {
                    if (! is_array($definition)) {
                        continue;
                    }
                    $resolvedId = trim((string) ($definition['id'] ?? $yamlKey));
                    if ($resolvedId === '') {
                        continue;
                    }
                    if (isset($merged[$resolvedId]) && $state->log !== null) {
                        $state->log->add(sprintf(
                            'Form tabs: field id "%s" is declared more than once (last declaration wins).',
                            $resolvedId,
                        ));
                    }
                    $merged[$resolvedId] = $definition;
                    $fieldIdsOrdered[] = $resolvedId;
                }
            }

            $entry = [
                'id' => $tabIdStr,
                'label' => $label,
                'field_ids' => $fieldIdsOrdered,
            ];
            if ($icon !== null) {
                $entry['icon'] = $icon;
            }
            $tabPanels[] = $entry;
        }

        $state->schema['fields'] = $merged;
        $state->schema['tab_panels'] = $tabPanels;
        unset($state->schema['tabs']);

        return $next($state);
    }
}
