<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Widgets\Normalization\WidgetSchemaNormalizationSupport;
use Flatpack\Schema\Widgets\Normalization\WidgetSchemaPipelineState;

final readonly class NormalizeStatusWidgetsPipe
{
    public function __construct(
        private WidgetSchemaNormalizationSupport $support,
    ) {}

    public function handle(WidgetSchemaPipelineState $state, Closure $next): mixed
    {
        foreach ($state->pendingEntries as $entry) {
            if ($entry['type'] !== 'status') {
                continue;
            }
            $result = $this->support->normalizeStatusWidgetDefinition(
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
