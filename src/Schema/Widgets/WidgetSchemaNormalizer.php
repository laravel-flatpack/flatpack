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
                if (! in_array($type, ['metric', 'card', 'status', 'chart'], true)) {
                    $debug?->add(sprintf('widgets.%s ignored: unsupported type "%s".', $widgetId, $type));

                    continue;
                }

                $provider = trim((string) ($definition['provider'] ?? ''));
                $label = trim((string) ($definition['label'] ?? ''));
                if ($provider === '' || $label === '') {
                    $debug?->add(sprintf('widgets.%s ignored: requires non-empty provider and label.', $widgetId));

                    continue;
                }

                if ($type === 'chart') {
                    $chartConfig = $this->normalizeChartWidgetConfig($definition['chart'] ?? null, $debug, $widgetId);
                    if ($chartConfig === null) {
                        continue;
                    }
                    $normalizedWidgets[$widgetId] = [
                        'type' => 'chart',
                        'provider' => $provider,
                        'label' => $label,
                        'description' => isset($definition['description']) ? (string) $definition['description'] : null,
                        'chart' => $chartConfig,
                    ];

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
    private function normalizeChartWidgetConfig(mixed $raw, ?CompositionDebugLog $debug, string $widgetId): ?array
    {
        if (! is_array($raw)) {
            $debug?->add(sprintf('widgets.%s ignored: chart widget requires a chart configuration object.', $widgetId));

            return null;
        }

        $xKey = trim((string) ($raw['x_key'] ?? ''));
        if ($xKey === '') {
            $xKey = 'date';
        }

        $seriesRaw = $raw['series'] ?? null;
        if (! is_array($seriesRaw) || $seriesRaw === []) {
            $debug?->add(sprintf('widgets.%s ignored: chart.series must be a non-empty array.', $widgetId));

            return null;
        }

        $series = [];
        foreach ($seriesRaw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $key = trim((string) ($item['key'] ?? ''));
            $seriesLabel = trim((string) ($item['label'] ?? ''));
            if ($key === '' || $seriesLabel === '') {
                continue;
            }
            $entry = [
                'key' => $key,
                'label' => $seriesLabel,
            ];
            if (isset($item['color']) && is_string($item['color'])) {
                $color = trim($item['color']);
                if ($color !== '') {
                    $entry['color'] = $color;
                }
            }
            $series[] = $entry;
        }

        if ($series === []) {
            $debug?->add(sprintf('widgets.%s ignored: chart.series must include at least one key and label.', $widgetId));

            return null;
        }

        $mode = trim((string) ($raw['mode'] ?? ''));
        if ($mode === '') {
            $mode = 'area';
        }
        if (! in_array($mode, ['area', 'bar', 'line'], true)) {
            $debug?->add(sprintf('widgets.%s ignored: chart.mode must be area, bar, or line.', $widgetId));

            return null;
        }

        $variant = 'area_stacked';
        if ($mode === 'area') {
            $variant = trim((string) ($raw['variant'] ?? ''));
            if ($variant === '') {
                $variant = 'area_stacked';
            }
            if (! in_array($variant, ['area_stacked', 'area'], true)) {
                $debug?->add(sprintf('widgets.%s ignored: chart.variant must be area_stacked or area.', $widgetId));

                return null;
            }
        }

        $timeRangesRaw = $raw['time_ranges'] ?? null;
        $timeRanges = [];
        if (is_array($timeRangesRaw)) {
            foreach ($timeRangesRaw as $range) {
                if (! is_array($range)) {
                    continue;
                }
                $id = trim((string) ($range['id'] ?? ''));
                $rangeLabel = trim((string) ($range['label'] ?? ''));
                if ($id === '' || $rangeLabel === '') {
                    continue;
                }
                $timeRanges[] = [
                    'id' => $id,
                    'label' => $rangeLabel,
                ];
            }
        }

        $normalized = [
            'x_key' => $xKey,
            'mode' => $mode,
            'variant' => $variant,
            'series' => $series,
        ];

        if ($timeRanges !== []) {
            $normalized['time_ranges'] = $timeRanges;
        }

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
