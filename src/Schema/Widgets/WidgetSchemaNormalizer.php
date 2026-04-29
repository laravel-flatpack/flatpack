<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets;

use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Support\CompositionDebugLog;

final class WidgetSchemaNormalizer
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    public function normalize(?array $schema, ?CompositionDebugLog $debug = null): ?array
    {
        if ($schema === null) {
            return null;
        }

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

        $normalizedWidgets = [];
        foreach ($widgetSources as $widgets) {
            foreach ($widgets as $widgetId => $definition) {
                if (! is_string($widgetId) || $widgetId === '' || ! is_array($definition)) {
                    $debug?->add(sprintf('widgets.%s ignored: expected object definition.', (string) $widgetId));

                    continue;
                }

                $type = trim((string) ($definition['type'] ?? ''));
                if (! in_array($type, ['metric', 'card', 'status'], true)) {
                    $debug?->add(sprintf('widgets.%s ignored: unsupported type "%s".', $widgetId, $type));

                    continue;
                }

                $provider = trim((string) ($definition['provider'] ?? ''));
                $label = trim((string) ($definition['label'] ?? ''));
                if ($provider === '' || $label === '') {
                    $debug?->add(sprintf('widgets.%s ignored: requires non-empty provider and label.', $widgetId));

                    continue;
                }

                if ($type === 'metric') {
                    $normalizedWidgets[$widgetId] = [
                        'type' => 'metric',
                        'provider' => $provider,
                        'label' => $label,
                        'description' => isset($definition['description']) ? (string) $definition['description'] : null,
                        'value_format' => is_array($definition['value_format'] ?? null) ? $definition['value_format'] : ['kind' => 'number'],
                        'period' => is_array($definition['period'] ?? null) ? $definition['period'] : ['kind' => 'custom'],
                        'trend' => is_array($definition['trend'] ?? null) ? $definition['trend'] : null,
                    ];

                    continue;
                }

                if ($type === 'card') {
                    $normalizedWidgets[$widgetId] = [
                        'type' => 'card',
                        'provider' => $provider,
                        'label' => $label,
                        'description' => isset($definition['description']) ? (string) $definition['description'] : null,
                        'data' => $this->normalizeStatusWidgetData($definition['data'] ?? null),
                    ];

                    continue;
                }

                $normalizedWidgets[$widgetId] = [
                    'type' => 'status',
                    'provider' => $provider,
                    'label' => $label,
                    'description' => isset($definition['description']) ? (string) $definition['description'] : null,
                    'data' => $this->normalizeStatusWidgetData($definition['data'] ?? null),
                ];
            }
        }

        $normalized['widgets'] = $normalizedWidgets;

        return $normalized;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeStatusWidgetData(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $normalized = [];
        $status = $this->normalizeWidgetStatus($raw['status'] ?? null);
        if ($status !== null) {
            $normalized['status'] = $status;
        }
        if (isset($raw['value']) && (is_string($raw['value']) || is_numeric($raw['value']))) {
            $normalized['value'] = is_string($raw['value']) ? $raw['value'] : (float) $raw['value'];
        }
        foreach (['context', 'updated_at', 'description'] as $key) {
            if (isset($raw[$key]) && is_string($raw[$key])) {
                $value = trim($raw[$key]);
                if ($value !== '') {
                    $normalized[$key] = $value;
                }
            }
        }

        return $normalized === [] ? null : $normalized;
    }

    /**
     * @return 'warning'|'error'|'success'|'info'|'default'|null
     */
    private function normalizeWidgetStatus(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $status = trim($raw);
        if (! in_array($status, CompositionSchemaKeys::WIDGET_STATUS_VALUES, true)) {
            return null;
        }

        return $status;
    }
}
