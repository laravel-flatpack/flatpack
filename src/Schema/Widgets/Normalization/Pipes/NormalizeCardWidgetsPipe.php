<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Widgets\Normalization\WidgetSchemaNormalizationSupport;
use Flatpack\Schema\Widgets\Normalization\WidgetSchemaPipelineState;

final class NormalizeCardWidgetsPipe
{
    public function handle(WidgetSchemaPipelineState $state, Closure $next): mixed
    {
        foreach ($state->pendingEntries as $entry) {
            if ($entry['type'] !== 'card') {
                continue;
            }
            $result = WidgetSchemaNormalizationSupport::normalizeCardWidgetDefinition(
                $entry['definition'],
                $entry['provider'],
                $entry['label'],
                $state->log,
                $entry['widgetId'],
            );
            if ($result !== null) {
                $state->normalized['widgets'][$entry['widgetId']] = $result;
            }
        }

        return $next($state);
    }
}
