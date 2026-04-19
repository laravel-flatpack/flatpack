<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Forms\Normalization\FormSchemaPipelineState;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Support\CompositionNestedKeyWarnings;

final class WarnUnknownFormActionsNestedKeysPipe
{
    public function handle(FormSchemaPipelineState $state, Closure $next): mixed
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
