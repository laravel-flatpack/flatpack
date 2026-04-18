<?php

declare(strict_types=1);

namespace Flatpack\Support\FormSchema\Pipes;

use Closure;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Support\FormSchema\FormSchemaPipelineState;

final class LogUnknownFormRootKeysPipe
{
    public function handle(FormSchemaPipelineState $state, Closure $next): mixed
    {
        if ($state->log === null) {
            return $next($state);
        }

        $keys = array_keys($state->schema);
        sort($keys, SORT_STRING);

        foreach ($keys as $key) {
            if (! in_array($key, CompositionSchemaKeys::FORM_ROOT_PROPERTY_KEYS, true)) {
                $state->log->add(sprintf('Unknown top-level form key "%s" (ignored at runtime).', $key));
            }
        }

        return $next($state);
    }
}
