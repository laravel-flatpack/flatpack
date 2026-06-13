<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Schema\Lists\Normalization\ListSchemaPipelineState;
use Flatpack\Support\CompositionNestedKeyWarnings;

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
