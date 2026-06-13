<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets;

use Flatpack\Schema\ResolvesLaravelPipeline;
use Flatpack\Schema\Widgets\Normalization\Pipes\CollectWidgetDefinitionsPipe;
use Flatpack\Schema\Widgets\Normalization\Pipes\NormalizeWidgetDefinitionsPipe;
use Flatpack\Schema\Widgets\Normalization\WidgetSchemaNormalizationSupport;
use Flatpack\Schema\Widgets\Normalization\WidgetSchemaPipelineState;
use Flatpack\Support\CompositionDebugContext;
use Flatpack\Support\CompositionDebugLog;
use Illuminate\Pipeline\Pipeline;

/**
 * Prepares widget YAML schema for Inertia / JSON responses.
 * Normalization runs as a Laravel {@see Pipeline} of discrete stages.
 */
final readonly class WidgetSchemaNormalizer
{
    use ResolvesLaravelPipeline;

    public function __construct(
        private WidgetSchemaNormalizationSupport $support,
        private ?Pipeline $pipeline = null,
    ) {}

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    public function normalize(?array $schema, ?CompositionDebugLog $debug = null): ?array
    {
        if ($schema === null) {
            return null;
        }

        $state = new WidgetSchemaPipelineState($schema, CompositionDebugContext::resolveOptional($debug));

        /** @var WidgetSchemaPipelineState $out */
        $out = $this->resolvePipeline()
            ->send($state)
            ->through([
                CollectWidgetDefinitionsPipe::class,
                NormalizeWidgetDefinitionsPipe::class,
            ])
            ->thenReturn();

        return $out->normalized;
    }

    /**
     * @return 'warning'|'error'|'success'|'info'|'default'|null
     */
    public function normalizeWidgetStatusValue(mixed $raw): ?string
    {
        return $this->support->normalizeWidgetStatusValue($raw);
    }

    /**
     * Normalizes column definitions returned from a provider-backed table widget (list or keyed map).
     *
     * @param  list<array<string, mixed>>|array<string, mixed>  $columns
     * @return array<string, mixed>
     */
    public function normalizeProviderResolvedTableColumns(mixed $columns): array
    {
        return $this->support->normalizeProviderResolvedTableColumns($columns);
    }
}
