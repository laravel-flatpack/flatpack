<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Widgets\Normalization\WidgetSchemaNormalizationSupport;
use Flatpack\Schema\Widgets\Normalization\WidgetSchemaPipelineState;

final readonly class NormalizeTableWidgetsPipe
{
    public function __construct(
        private WidgetSchemaNormalizationSupport $support,
    ) {}

    public function handle(WidgetSchemaPipelineState $state, Closure $next): mixed
    {
        foreach ($state->pendingEntries as $entry) {
            if ($entry['type'] !== 'table') {
                continue;
            }
            $result = $this->support->normalizeTableWidgetConfig(
                $entry['definition'],
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
