<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Facades\Flatpack;
use Flatpack\Http\FlatpackResponse;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Flatpack\Services\Runtime\WidgetRuntime;
use Flatpack\Support\Exceptions\WidgetRuntimeException;
use Flatpack\Widgets\WidgetDataContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final readonly class DashboardController
{
    public function __construct(
        private CompositionQuery $compositions,
        private WidgetSchemaNormalizer $widgetSchemaNormalizer,
        private WidgetRuntime $widgetRuntime,
    ) {}

    public function index(Request $request): Response|JsonResponse
    {
        $debugLog = FlatpackResponse::compositionDebugLog(FlatpackResponse::compositionDebugContextForDashboard());

        /** @var array<string, mixed>|null $schema */
        $schema = $this->compositions->optional(
            Flatpack::dashboardEntity(),
            'list'
        );
        /** @var array<string, mixed>|null $widgets */
        $widgets = $this->compositions->optional(
            Flatpack::dashboardEntity(),
            'widgets'
        );
        $normalizedWidgets = $this->widgetSchemaNormalizer->normalize($widgets, $debugLog);
        $resolvedWidgets = $this->resolveWidgetData(
            $request,
            Flatpack::dashboardEntity(),
            $normalizedWidgets['widgets'] ?? [],
            $debugLog,
        );

        return FlatpackResponse::inertia('dashboard', [
            'schema' => $schema,
            'widgets' => $resolvedWidgets,
            'widgets_schema' => $normalizedWidgets,
            'composition_debug' => $debugLog?->all() ?? [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $widgets
     * @return array<string, array<string, mixed>>
     */
    private function resolveWidgetData(
        Request $request,
        string $entity,
        array $widgets,
        ?\Flatpack\Support\CompositionDebugLog $debugLog = null,
    ): array {
        $resolved = [];

        foreach ($widgets as $widgetId => $definition) {
            if (! is_string($widgetId) || ! is_array($definition)) {
                continue;
            }

            $providerKey = trim((string) ($definition['provider'] ?? ''));
            if ($providerKey === '') {
                $debugLog?->add(sprintf('widgets.%s ignored: missing provider.', $widgetId));
                continue;
            }

            try {
                $provider = $this->widgetRuntime->resolveProvider($providerKey);
                $data = $this->widgetRuntime->resolveData(
                    $provider,
                    new WidgetDataContext(
                        request: $request,
                        entity: $entity,
                        widgetId: $widgetId,
                        definition: $definition,
                    ),
                );
            } catch (WidgetRuntimeException $exception) {
                $debugLog?->add(sprintf('widgets.%s provider error: %s', $widgetId, $exception->getMessage()));
                $data = [];
            } catch (AuthorizationException $exception) {
                $debugLog?->add(sprintf('widgets.%s unauthorized: %s', $widgetId, $exception->getMessage()));
                $data = [];
            }

            $resolved[$widgetId] = [
                ...$definition,
                'data' => $this->normalizeResolvedWidgetData($definition, $data),
            ];
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  mixed  $rawData
     * @return array<string, mixed>
     */
    private function normalizeResolvedWidgetData(array $definition, mixed $rawData): array
    {
        $data = is_array($rawData) ? $rawData : [];
        $type = isset($definition['type']) ? trim((string) $definition['type']) : '';
        if ($type !== 'card') {
            return $data;
        }

        $status = $this->normalizeWidgetStatus($data['status'] ?? null);
        $data['status'] = $status ?? 'default';

        return $data;
    }

    /**
     * @return 'warning'|'error'|'success'|'info'|'default'|null
     */
    private function normalizeWidgetStatus(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $status = trim($raw);
        if (! in_array($status, CompositionSchemaKeys::WIDGET_STATUS_VALUES, true)) {
            return null;
        }

        return $status;
    }
}
