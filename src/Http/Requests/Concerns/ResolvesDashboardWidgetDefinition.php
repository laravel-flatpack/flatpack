<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests\Concerns;

use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Facades\Flatpack;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;

trait ResolvesDashboardWidgetDefinition
{
    /**
     * @return array<string, mixed>|null
     */
    private function resolveDashboardWidgetDefinition(string $widgetId): ?array
    {
        if ($widgetId === '') {
            return null;
        }

        /** @var CompositionQuery $compositions */
        $compositions = $this->container->make(CompositionQuery::class);
        /** @var array<string, mixed>|null $schema */
        $schema = $compositions->optional(Flatpack::dashboardEntity(), 'list');
        /** @var WidgetSchemaNormalizer $normalizer */
        $normalizer = $this->container->make(WidgetSchemaNormalizer::class);
        $normalized = $normalizer->normalize($schema);
        $definition = $normalized['widgets'][$widgetId] ?? null;

        return is_array($definition) ? $definition : null;
    }
}
