<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Widgets\Normalization\WidgetSchemaPipelineState;

/**
 * Collects tab panels metadata and ordered widget entries from root widgets and tab widgets.
 */
final class CollectWidgetDefinitionsPipe
{
    public function handle(WidgetSchemaPipelineState $state, Closure $next): mixed
    {
        $schema = $state->inputSchema;
        $debug = $state->log;

        $normalized = [
            'widgets' => [],
        ];
        /** @var array<int, array<string, mixed>> $widgetSources */
        $widgetSources = [];

        $rootWidgets = $schema['widgets'] ?? null;
        if (is_array($rootWidgets)) {
            $widgetSources[] = $rootWidgets;
        }

        $tabs = $schema['tabs'] ?? null;
        if (is_array($tabs)) {
            $tabPanels = [];
            foreach ($tabs as $tabId => $tabDefinition) {
                if (! is_string($tabId) || $tabId === '' || ! is_array($tabDefinition)) {
                    $debug?->add(sprintf('tabs.%s ignored: expected object definition.', (string) $tabId));

                    continue;
                }
                $label = trim((string) ($tabDefinition['label'] ?? ''));
                if ($label === '') {
                    $debug?->add(sprintf('tabs.%s ignored: requires non-empty label.', $tabId));

                    continue;
                }
                $tabWidgets = $tabDefinition['widgets'] ?? null;
                if (! is_array($tabWidgets)) {
                    $debug?->add(sprintf('tabs.%s ignored: requires widgets map.', $tabId));

                    continue;
                }

                $widgetSources[] = $tabWidgets;
                $tabPanels[] = [
                    'id' => $tabId,
                    'label' => $label,
                    'icon' => isset($tabDefinition['icon']) ? trim((string) $tabDefinition['icon']) : null,
                    'widget_ids' => array_values(array_filter(array_map(
                        static fn (mixed $widgetId): ?string => is_string($widgetId) && $widgetId !== ''
                            ? $widgetId
                            : null,
                        array_keys($tabWidgets)
                    ))),
                ];
            }
            if ($tabPanels !== []) {
                $normalized['tab_panels'] = $tabPanels;
            }
        }

        $pendingEntries = [];
        foreach ($widgetSources as $widgets) {
            foreach ($widgets as $widgetId => $definition) {
                if (! is_string($widgetId) || $widgetId === '' || ! is_array($definition)) {
                    $debug?->add(sprintf('widgets.%s ignored: expected object definition.', (string) $widgetId));

                    continue;
                }

                $type = trim((string) ($definition['type'] ?? ''));
                if (! in_array($type, ['metric', 'card', 'status', 'chart', 'table', 'grid'], true)) {
                    $debug?->add(sprintf('widgets.%s ignored: unsupported type "%s".', $widgetId, $type));

                    continue;
                }

                $label = trim((string) ($definition['label'] ?? ''));
                if (! in_array($type, ['table', 'grid'], true) && $label === '') {
                    $debug?->add(sprintf('widgets.%s ignored: requires non-empty label.', $widgetId));

                    continue;
                }

                $provider = trim((string) ($definition['provider'] ?? ''));
                $pendingEntries[] = [
                    'widgetId' => $widgetId,
                    'type' => $type,
                    'definition' => $definition,
                    'provider' => $provider,
                    'label' => $label,
                ];
            }
        }

        $state->normalized = $normalized;
        $state->pendingEntries = $pendingEntries;

        return $next($state);
    }
}
