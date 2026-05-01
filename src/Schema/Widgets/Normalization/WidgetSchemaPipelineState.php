<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets\Normalization;

use Flatpack\Support\CompositionDebugLog;

/**
 * Passable for {@see \Flatpack\Schema\Widgets\WidgetSchemaNormalizer} pipeline: widgets schema plus optional debug log.
 */
final class WidgetSchemaPipelineState
{
    /**
     * @param  array<string, mixed>  $inputSchema
     * @param  array<string, mixed>  $normalized
     * @param  list<array{
     *     widgetId: string,
     *     type: string,
     *     definition: array<string, mixed>,
     *     provider: string,
     *     label: string,
     * }>  $pendingEntries
     */
    public function __construct(
        public array $inputSchema,
        public ?CompositionDebugLog $log,
        public array $normalized = [
            'widgets' => [],
        ],
        public array $pendingEntries = [],
    ) {}
}
