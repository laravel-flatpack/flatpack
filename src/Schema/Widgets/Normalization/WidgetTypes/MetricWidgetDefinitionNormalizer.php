<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets\Normalization\WidgetTypes;

use Flatpack\Schema\Forms\Normalization\FieldSpanCanonicalizer;
use Flatpack\Support\CompositionDebugLog;

/**
 * Normalizes metric widget definitions from widget YAML.
 */
final readonly class MetricWidgetDefinitionNormalizer
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
            $debug?->add(sprintf('widgets.%s ignored: metric widget requires non-empty provider.', $widgetId));

            return null;
        }

        $result = [
            'type' => 'metric',
            'provider' => $provider,
            'label' => $label,
            'description' => isset($definition['description']) ? (string) $definition['description'] : null,
            'value_format' => is_array($definition['value_format'] ?? null) ? $definition['value_format'] : ['kind' => 'number'],
            'period' => is_array($definition['period'] ?? null) ? $definition['period'] : ['kind' => 'custom'],
            'trend' => is_array($definition['trend'] ?? null) ? $definition['trend'] : null,
        ];
        FieldSpanCanonicalizer::mergeIntoIfPresent(
            $definition,
            $result,
            sprintf('widgets.%s', $widgetId),
            $debug,
        );

        return $result;
    }
}
