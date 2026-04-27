<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Schema\Lists\Normalization\ListSchemaPipelineState;

final class WarnInvalidListMenuPipe
{
    /** @var list<string> */
    private const array ALLOWED = ['main', 'secondary', 'bottom'];

    public function handle(ListSchemaPipelineState $state, Closure $next): mixed
    {
        $key = CompositionSchemaKeys::LIST_ROOT['menu'];
        if (! isset($state->schema[$key])) {
            return $next($state);
        }

        $raw = $state->schema[$key];
        if (is_string($raw)) {
            $trimmed = trim($raw);
            if (in_array($trimmed, self::ALLOWED, true)) {
                $state->schema[$key] = $trimmed;

                return $next($state);
            }
        }

        if ($state->log !== null) {
            $shown = is_string($raw)
                ? sprintf('"%s"', $raw)
                : json_encode($raw);
            $state->log->add(sprintf(
                'Invalid list `menu` value %s (expected main, secondary, or bottom); ignored, using main.',
                $shown,
            ));
        }

        $state->schema[$key] = 'main';

        return $next($state);
    }
}
