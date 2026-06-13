<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets\Normalization\WidgetTypes;

use Flatpack\Schema\Forms\Normalization\FieldSpanCanonicalizer;
use Flatpack\Support\CompositionDebugLog;

/**
 * Normalizes chart widget definitions from widget YAML.
 */
final readonly class ChartWidgetDefinitionNormalizer
{
    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>|null
     */
    public function normalize(
        string $widgetId,
        array $definition,
        string $provider,
        string $label,
        ?CompositionDebugLog $debug,
    ): ?array {
        if ($provider === '') {
            $debug?->add(sprintf('widgets.%s ignored: chart widget requires non-empty provider.', $widgetId));

            return null;
        }
        $chartConfig = $this->normalizeChartWidgetConfig($definition['chart'] ?? null, $debug, $widgetId);
        if ($chartConfig === null) {
            return null;
        }

        $result = [
            'type' => 'chart',
            'provider' => $provider,
            'label' => $label,
            'description' => isset($definition['description']) ? (string) $definition['description'] : null,
            'chart' => $chartConfig,
        ];
        FieldSpanCanonicalizer::mergeIntoIfPresent(
            $definition,
            $result,
            sprintf('widgets.%s', $widgetId),
            $debug,
        );

        return $result;
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
}
