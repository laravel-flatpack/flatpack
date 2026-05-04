<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets\Normalization\WidgetTypes;

use Flatpack\Support\CompositionDebugLog;

/**
 * Normalizes table widget definitions from widget YAML.
 */
final readonly class TableWidgetDefinitionNormalizer
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
        return $this->tableLike->normalizeTableWidgetConfig($definition, $debug, $widgetId);
    }
}
