<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Widgets\Normalization\WidgetSchemaPipelineState;
use Flatpack\Schema\Widgets\Normalization\WidgetTypes\CardWidgetDefinitionNormalizer;
use Flatpack\Schema\Widgets\Normalization\WidgetTypes\ChartWidgetDefinitionNormalizer;
use Flatpack\Schema\Widgets\Normalization\WidgetTypes\GridWidgetDefinitionNormalizer;
use Flatpack\Schema\Widgets\Normalization\WidgetTypes\MetricWidgetDefinitionNormalizer;
use Flatpack\Schema\Widgets\Normalization\WidgetTypes\StatusWidgetDefinitionNormalizer;
use Flatpack\Schema\Widgets\Normalization\WidgetTypes\TableWidgetDefinitionNormalizer;

/**
 * Normalizes pending widget definitions in {@see CollectWidgetDefinitionsPipe}'s schema order.
 */
final readonly class NormalizeWidgetDefinitionsPipe
{
    public function __construct(
        private ChartWidgetDefinitionNormalizer $chart,
        private MetricWidgetDefinitionNormalizer $metric,
        private CardWidgetDefinitionNormalizer $card,
        private StatusWidgetDefinitionNormalizer $status,
        private TableWidgetDefinitionNormalizer $table,
        private GridWidgetDefinitionNormalizer $grid,
    ) {}

    public function handle(WidgetSchemaPipelineState $state, Closure $next): mixed
    {
        foreach ($state->pendingEntries as $entry) {
            $result = match ($entry['type']) {
                'chart' => $this->chart->normalize(
                    $entry['widgetId'],
                    $entry['definition'],
                    $entry['provider'],
                    $entry['label'],
                    $state->log,
                ),
                'metric' => $this->metric->normalize(
                    $entry['widgetId'],
                    $entry['definition'],
                    $entry['provider'],
                    $entry['label'],
                    $state->log,
                ),
                'card' => $this->card->normalize(
                    $entry['widgetId'],
                    $entry['definition'],
                    $entry['provider'],
                    $entry['label'],
                    $state->log,
                ),
                'status' => $this->status->normalize(
                    $entry['widgetId'],
                    $entry['definition'],
                    $entry['provider'],
                    $entry['label'],
                    $state->log,
                ),
                'table' => $this->table->normalize(
                    $entry['widgetId'],
                    $entry['definition'],
                    $state->log,
                ),
                'grid' => $this->grid->normalize(
                    $entry['widgetId'],
                    $entry['definition'],
                    $state->log,
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
