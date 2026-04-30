<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Facades\Flatpack;
use Flatpack\Http\Controllers\Concerns\ResolvesWidgets;
use Flatpack\Http\FlatpackResponse;
use Flatpack\Http\FlatpackResponseOptions;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Flatpack\Services\Lists\ListRecordsLoader;
use Flatpack\Services\Runtime\WidgetRuntime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final readonly class DashboardController
{
    use ResolvesWidgets;

    public function __construct(
        private CompositionQuery $compositions,
        private WidgetSchemaNormalizer $widgetSchemaNormalizer,
        private WidgetRuntime $widgetRuntime,
        private ListRecordsLoader $listRecordsLoader,
    ) {}

    public function index(Request $request): Response|JsonResponse
    {
        $widgetDebugEnabled = $request->boolean('flatpack_widget_debug');
        $debugLog = FlatpackResponse::compositionDebugLog(FlatpackResponse::compositionDebugContextForDashboard());

        /** @var array<string, mixed>|null $schema */
        $schema = $this->compositions->optional(
            Flatpack::dashboardEntity(),
            'list'
        );

        $normalizedWidgets = $this->normalizedWidgetsSchema($schema, $debugLog);
        $resolvedWidgets = $this->resolveWidgetData(
            $request,
            Flatpack::dashboardEntity(),
            $normalizedWidgets['widgets'] ?? [],
            $debugLog,
        );
        if ($widgetDebugEnabled) {
            // `resolveWidgetData` returns a flat map: widgetId => definition+data (not nested under `widgets`).
            // Tab layout lives on the normalized schema (`widgets_schema`), not on resolved data.
            /** @var array<string, mixed> $normalizedForLog */
            $normalizedForLog = is_array($normalizedWidgets) ? $normalizedWidgets : [];
            logger()->info('[Flatpack][Dashboard] widget payload resolved', [
                'request_query' => $request->query->all(),
                'resolved_widget_ids' => array_keys($resolvedWidgets),
                'resolved_widget_types' => collect($resolvedWidgets)
                    ->map(
                        static fn (array $widget): string => (string) ($widget['type'] ?? 'unknown'),
                    )
                    ->all(),
                'schema_tab_panel_ids' => collect($normalizedForLog['tab_panels'] ?? [])
                    ->map(
                        static fn (mixed $panel): string => is_array($panel)
                            ? (string) ($panel['id'] ?? 'unknown')
                            : 'unknown',
                    )
                    ->all(),
                'schema_widget_ids' => array_keys($normalizedForLog['widgets'] ?? []),
            ]);
        }

        return FlatpackResponse::inertia(
            'dashboard',
            [
                'schema' => $schema,
                'widgets' => $resolvedWidgets,
                'widgets_schema' => $normalizedWidgets,
            ],
            new FlatpackResponseOptions(compositionDebugLog: $debugLog),
        );
    }

    private function widgetSchemaNormalizer(): WidgetSchemaNormalizer
    {
        return $this->widgetSchemaNormalizer;
    }

    private function widgetRuntime(): WidgetRuntime
    {
        return $this->widgetRuntime;
    }

    private function listRecordsLoader(): ListRecordsLoader
    {
        return $this->listRecordsLoader;
    }
}
