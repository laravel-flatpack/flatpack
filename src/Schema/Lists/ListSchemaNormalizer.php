<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists;

use Flatpack\Schema\Lists\Normalization\ListSchemaPipelineState;
use Flatpack\Schema\Lists\Normalization\Pipes\MergeListTabsIntoColumnsPipe;
use Flatpack\Schema\Lists\Normalization\Pipes\StripUnknownListRootKeysPipe;
use Flatpack\Schema\Lists\Normalization\Pipes\WarnInvalidListMenuPipe;
use Flatpack\Schema\Lists\Normalization\Pipes\WarnUnknownListBulkActionsNestedKeysPipe;
use Flatpack\Schema\Lists\Normalization\Pipes\WarnUnknownListColumnActionButtonsNestedKeysPipe;
use Flatpack\Schema\Lists\Normalization\Pipes\WarnUnknownListHeaderActionsNestedKeysPipe;
use Flatpack\Schema\ResolvesLaravelPipeline;
use Flatpack\Support\CompositionDebugContext;
use Flatpack\Support\CompositionDebugLog;
use Illuminate\Pipeline\Pipeline;

/**
 * Prepares list composition YAML for Inertia / JSON responses.
 * Normalization runs as a Laravel {@see Pipeline} of discrete validation / debug stages.
 */
final readonly class ListSchemaNormalizer
{
    use ResolvesLaravelPipeline;

    public function __construct(
        private ?Pipeline $pipeline = null,
    ) {}

    /**
     * @param  array<string, mixed>|null  $schema
     */
    public function normalizedListSchema(?array $schema, ?CompositionDebugLog $debug = null): ?NormalizedListSchema
    {
        if ($schema === null) {
            return null;
        }

        $state = new ListSchemaPipelineState($schema, CompositionDebugContext::resolveOptional($debug));

        /** @var ListSchemaPipelineState $out */
        $out = $this->resolvePipeline()
            ->send($state)
            ->through([
                MergeListTabsIntoColumnsPipe::class,
                WarnInvalidListMenuPipe::class,
                StripUnknownListRootKeysPipe::class,
                WarnUnknownListHeaderActionsNestedKeysPipe::class,
                WarnUnknownListBulkActionsNestedKeysPipe::class,
                WarnUnknownListColumnActionButtonsNestedKeysPipe::class,
            ])
            ->thenReturn();

        return new NormalizedListSchema($out->schema);
    }
}
