<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets\Normalization\WidgetTypes;

use Flatpack\Schema\Forms\Normalization\FieldSpanCanonicalizer;
use Flatpack\Schema\Widgets\Normalization\WidgetSchemaNormalizationSupport;
use Flatpack\Support\CompositionDebugLog;

/**
 * Normalizes card widget definitions from widget YAML.
 */
final readonly class CardWidgetDefinitionNormalizer
{
    public function __construct(
        private WidgetSchemaNormalizationSupport $shared,
    ) {}

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
            $debug?->add(sprintf('widgets.%s ignored: card widget requires non-empty provider.', $widgetId));

            return null;
        }

        $result = [
            'type' => 'card',
            'provider' => $provider,
            'label' => $label,
            'description' => isset($definition['description']) ? (string) $definition['description'] : null,
            'data' => $this->shared->normalizeStatusWidgetData($definition['data'] ?? null),
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
