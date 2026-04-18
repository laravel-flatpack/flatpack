<?php

declare(strict_types=1);

namespace Flatpack\Support\FormSchema\Pipes;

use Closure;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Support\CompositionNestedKeyWarnings;
use Flatpack\Support\FormSchema\FormSchemaPipelineState;

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
