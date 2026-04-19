<?php

declare(strict_types=1);

namespace Flatpack\Support\ListSchema\Pipes;

use Closure;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Support\CompositionNestedKeyWarnings;
use Flatpack\Support\ListSchema\ListSchemaPipelineState;

final class WarnUnknownListHeaderActionsNestedKeysPipe
{
    public function handle(ListSchemaPipelineState $state, Closure $next): mixed
    {
        if ($state->log === null) {
            return $next($state);
        }

        CompositionNestedKeyWarnings::forStringKeyedBlocks(
            $state->schema['actions'] ?? null,
            CompositionSchemaKeys::HEADER_ACTION_ENTRY_KEYS,
            'actions',
            $state->log,
        );

        return $next($state);
    }
}
