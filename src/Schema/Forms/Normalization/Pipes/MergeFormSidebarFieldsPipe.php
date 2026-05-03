<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Forms\Normalization\FormSchemaPipelineState;

/**
 * Flattens {@code sidebar} field definitions into the top-level {@code fields} map (same {@code values} payload)
 * and records {@code sidebar_field_ids} for UI placement. Optional {@code sidebar.widgets} is staged for widget normalization.
 */
final readonly class MergeFormSidebarFieldsPipe
{
    public function handle(FormSchemaPipelineState $state, Closure $next): mixed
    {
        $sidebar = $state->schema['sidebar'] ?? null;
        if (! is_array($sidebar) || $sidebar === []) {
            unset($state->schema['sidebar']);

            return $next($state);
        }

        $explicit =
            array_key_exists('fields', $sidebar) || array_key_exists('widgets', $sidebar);

        if ($explicit) {
            $sidebarFields = $sidebar['fields'] ?? null;
            $sidebarWidgets = $sidebar['widgets'] ?? null;
            if (! is_array($sidebarFields)) {
                $sidebarFields = [];
            }
            if (! is_array($sidebarWidgets)) {
                $sidebarWidgets = [];
            }
        } else {
            $sidebarFields = $sidebar;
            $sidebarWidgets = [];
        }

        /** @var array<string, mixed> $merged */
        $merged = [];
        $rootFields = $state->schema['fields'] ?? null;
        if (is_array($rootFields)) {
            foreach ($rootFields as $k => $v) {
                $merged[(string) $k] = $v;
            }
        }

        /** @var list<string> $sidebarFieldIds */
        $sidebarFieldIds = [];

        foreach ($sidebarFields as $yamlKey => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $resolvedId = trim((string) ($definition['id'] ?? $yamlKey));
            if ($resolvedId === '') {
                continue;
            }

            if (isset($merged[$resolvedId]) && $state->log !== null) {
                $state->log->add(sprintf(
                    'Form sidebar: field id "%s" is declared more than once (last declaration wins).',
                    $resolvedId,
                ));
            }

            $merged[$resolvedId] = $definition;
            $sidebarFieldIds[] = $resolvedId;
        }

        $state->schema['fields'] = $merged;
        $state->schema['sidebar_field_ids'] = $sidebarFieldIds;

        if ($sidebarWidgets !== []) {
            $state->schema['_sidebar_widgets_pending'] = $sidebarWidgets;
        }

        unset($state->schema['sidebar']);

        return $next($state);
    }
}
