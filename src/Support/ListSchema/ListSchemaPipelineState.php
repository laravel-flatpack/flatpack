<?php

declare(strict_types=1);

namespace Flatpack\Support\ListSchema;

use Flatpack\Support\CompositionDebugLog;

/**
 * Passable for {@see \Flatpack\Support\ListSchemaNormalizer} pipeline: mutable schema plus optional debug log.
 */
final class ListSchemaPipelineState
{
    /**
     * @param  array<string, mixed>  $schema
     */
    public function __construct(
        public array $schema,
        public ?CompositionDebugLog $log,
    ) {}
}
