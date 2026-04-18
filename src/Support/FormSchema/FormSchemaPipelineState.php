<?php

declare(strict_types=1);

namespace Flatpack\Support\FormSchema;

use Flatpack\Support\CompositionDebugLog;

/**
 * Passable for {@see FormSchemaNormalizer} pipeline: mutable schema plus optional debug log.
 */
final class FormSchemaPipelineState
{
    /**
     * @param  array<string, mixed>  $schema
     */
    public function __construct(
        public array $schema,
        public ?CompositionDebugLog $log,
    ) {}
}
