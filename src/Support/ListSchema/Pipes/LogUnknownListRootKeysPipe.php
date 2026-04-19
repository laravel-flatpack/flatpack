<?php

declare(strict_types=1);

namespace Flatpack\Support\ListSchema\Pipes;

use Closure;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Support\ListSchema\ListSchemaPipelineState;

final class LogUnknownListRootKeysPipe
{
    public function handle(ListSchemaPipelineState $state, Closure $next): mixed
    {
        if ($state->log === null) {
            return $next($state);
        }

        $keys = array_keys($state->schema);
        sort($keys, SORT_STRING);

        foreach ($keys as $key) {
            if (! in_array($key, CompositionSchemaKeys::LIST_ROOT_PROPERTY_KEYS, true)) {
                $state->log->add(sprintf('Unknown top-level list key "%s" (ignored at runtime).', $key));
            }
        }

        return $next($state);
    }
}
