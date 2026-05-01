<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Schema\Lists\Normalization\ListSchemaPipelineState;

final class StripUnknownListRootKeysPipe
{
    /** @var list<string> */
    private const array INTERNAL_NORMALIZED_KEYS = ['tab_panels', 'reorderableColumn'];

    public function handle(ListSchemaPipelineState $state, Closure $next): mixed
    {
        $keys = array_keys($state->schema);
        sort($keys, SORT_STRING);

        foreach ($keys as $key) {
            if (
                in_array($key, CompositionSchemaKeys::LIST_ROOT_PROPERTY_KEYS, true)
                || in_array($key, self::INTERNAL_NORMALIZED_KEYS, true)
            ) {
                continue;
            }
            unset($state->schema[$key]);
            $state->log?->add(sprintf('Unknown top-level list key "%s" (stripped at runtime).', $key));
        }

        return $next($state);
    }
}
