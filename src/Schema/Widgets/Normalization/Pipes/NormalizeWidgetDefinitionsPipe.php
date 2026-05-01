<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Widgets\Normalization\WidgetSchemaNormalizationSupport;
use Flatpack\Schema\Widgets\Normalization\WidgetSchemaPipelineState;

/**
 * Normalizes pending widget definitions in {@see CollectWidgetDefinitionsPipe}'s schema order.
 */
final readonly class NormalizeWidgetDefinitionsPipe
{
    public function __construct(
        private WidgetSchemaNormalizationSupport $support,
    ) {}

    public function handle(WidgetSchemaPipelineState $state, Closure $next): mixed
    {
        foreach ($state->pendingEntries as $entry) {
            $result = match ($entry['type']) {
                'chart' => $this->support->normalizeChartWidgetDefinition(
                    $entry['definition'],
                    $entry['provider'],
                    $entry['label'],
                    $state->log,
                    $entry['widgetId'],
                ),
                'metric' => $this->support->normalizeMetricWidgetDefinition(
                    $entry['definition'],
                    $entry['provider'],
                    $entry['label'],
                    $state->log,
                    $entry['widgetId'],
                ),
                'card' => $this->support->normalizeCardWidgetDefinition(
                    $entry['definition'],
                    $entry['provider'],
                    $entry['label'],
                    $state->log,
                    $entry['widgetId'],
                ),
                'status' => $this->support->normalizeStatusWidgetDefinition(
                    $entry['definition'],
                    $entry['provider'],
                    $entry['label'],
                    $state->log,
                    $entry['widgetId'],
                ),
                'table' => $this->support->normalizeTableWidgetConfig(
                    $entry['definition'],
                    $state->log,
                    $entry['widgetId'],
                ),
                default => null,
            };

            if ($result !== null) {
                $state->normalized['widgets'][$entry['widgetId']] = $result;
            }
        }

        return $next($state);
    }
}
