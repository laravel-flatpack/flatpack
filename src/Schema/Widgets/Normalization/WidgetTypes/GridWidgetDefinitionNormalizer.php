<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets\Normalization\WidgetTypes;

use Flatpack\Support\CompositionDebugLog;

/**
 * Normalizes grid widget definitions from widget YAML.
 */
final readonly class GridWidgetDefinitionNormalizer
{
    public function __construct(
        private TableLikeWidgetNormalizer $tableLike,
    ) {}

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>|null
     */
    public function normalize(string $widgetId, array $definition, ?CompositionDebugLog $debug): ?array
    {
        return $this->tableLike->normalizeGridWidgetConfig($definition, $debug, $widgetId);
    }
}
