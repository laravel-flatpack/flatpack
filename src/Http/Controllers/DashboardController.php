<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Facades\Flatpack;
use Flatpack\Http\Controllers\Concerns\ResolvesWidgets;
use Flatpack\Http\FlatpackResponse;
use Flatpack\Schema\Lists\ListSchemaNormalizer;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Flatpack\Services\Lists\ListRecordsLoader;
use Flatpack\Services\Runtime\WidgetRuntime;
use Flatpack\Support\CompositionDebugContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final readonly class DashboardController
{
    use ResolvesWidgets;

    public function __construct(
        private CompositionQuery $compositions,
        private ListSchemaNormalizer $listSchemaNormalizer,
        private WidgetSchemaNormalizer $widgetSchemaNormalizer,
        private WidgetRuntime $widgetRuntime,
        private ListRecordsLoader $listRecordsLoader,
        private CompositionDebugContext $compositionDebug,
    ) {}

    public function index(Request $request): Response|JsonResponse
    {
        $widgetDebugEnabled = $request->boolean('flatpack_widget_debug');
        $this->compositionDebug->activate(FlatpackResponse::compositionDebugContextForDashboard());

        /** @var array<string, mixed>|null $schema */
        $schema = $this->compositions->optional(
            Flatpack::dashboardEntity(),
            'list'
        );
        $normalizedSchema = $this->listSchemaNormalizer->normalizedListSchema($schema);
        $normalizedSchemaArray = $normalizedSchema?->toArray() ?? [];
        $modelClass = is_string($normalizedSchemaArray['model'] ?? null)
            ? trim((string) $normalizedSchemaArray['model'])
            : null;

        $normalizedWidgets = $this->normalizedWidgetsSchema($normalizedSchemaArray);
        $resolvedWidgets = $this->resolveWidgetDataWhenPresent(
            $request,
            Flatpack::dashboardEntity(),
            $normalizedWidgets['widgets'] ?? [],
        );
        if ($widgetDebugEnabled) {
            // `resolveWidgetData` returns a flat map: widgetId => definition+data (not nested under `widgets`).
            // Tab layout lives on the normalized widget schema (`schema`), not on resolved data.
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
                'model' => $modelClass,
                'widgets' => $resolvedWidgets,
                'schema' => $normalizedWidgets,
            ],
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
