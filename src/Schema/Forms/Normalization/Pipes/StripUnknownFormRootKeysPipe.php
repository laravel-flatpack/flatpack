<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Forms\Normalization\FormSchemaPipelineState;
use Flatpack\Schema\Generated\CompositionSchemaKeys;

final class StripUnknownFormRootKeysPipe
{
    /** @var list<string> */
    private const array INTERNAL_NORMALIZED_KEYS = ['tab_panels'];

    public function handle(FormSchemaPipelineState $state, Closure $next): mixed
    {
        $keys = array_keys($state->schema);
        sort($keys, SORT_STRING);

        foreach ($keys as $key) {
            if (
                in_array($key, CompositionSchemaKeys::FORM_ROOT_PROPERTY_KEYS, true)
                || in_array($key, self::INTERNAL_NORMALIZED_KEYS, true)
            ) {
                continue;
            }
            unset($state->schema[$key]);
            $state->log?->add(sprintf('Unknown top-level form key "%s" (stripped at runtime).', $key));
        }

        return $next($state);
    }
}
