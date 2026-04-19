<?php

declare(strict_types=1);

namespace Flatpack\Support;

use Flatpack\Support\ListSchema\ListSchemaPipelineState;
use Flatpack\Support\ListSchema\Pipes\LogUnknownListRootKeysPipe;
use Flatpack\Support\ListSchema\Pipes\WarnUnknownListBulkActionsNestedKeysPipe;
use Flatpack\Support\ListSchema\Pipes\WarnUnknownListHeaderActionsNestedKeysPipe;
use Illuminate\Pipeline\Pipeline;

/**
 * Prepares list composition YAML for Inertia / JSON responses.
 * Normalization runs as a Laravel {@see Pipeline} of discrete validation / debug stages.
 */
final readonly class ListSchemaNormalizer
{
    public function __construct(
        private ?Pipeline $pipeline = null,
    ) {}

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    public function normalizedListSchema(?array $schema, ?CompositionDebugLog $debug = null): ?array
    {
        if ($schema === null) {
            return null;
        }

        $state = new ListSchemaPipelineState($schema, $debug);

        /** @var ListSchemaPipelineState $out */
        $out = $this->resolvePipeline()
            ->send($state)
            ->through([
                LogUnknownListRootKeysPipe::class,
                WarnUnknownListHeaderActionsNestedKeysPipe::class,
                WarnUnknownListBulkActionsNestedKeysPipe::class,
            ])
            ->thenReturn();

        return $out->schema;
    }

    private function resolvePipeline(): Pipeline
    {
        return $this->pipeline ?? app(Pipeline::class);
    }
}
