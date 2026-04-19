<?php

declare(strict_types=1);

namespace Flatpack\Support\ListSchema\Pipes;

use Closure;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Support\CompositionNestedKeyWarnings;
use Flatpack\Support\ListSchema\ListSchemaPipelineState;

final class WarnUnknownListBulkActionsNestedKeysPipe
{
    public function handle(ListSchemaPipelineState $state, Closure $next): mixed
    {
        if ($state->log === null) {
            return $next($state);
        }

        CompositionNestedKeyWarnings::forStringKeyedBlocks(
            $state->schema['bulk_actions'] ?? null,
            CompositionSchemaKeys::LIST_BULK_ACTION_ENTRY_KEYS,
            'bulk_actions',
            $state->log,
        );

        return $next($state);
    }
}
