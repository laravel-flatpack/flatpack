<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Facades\Flatpack;
use Flatpack\Http\FlatpackResponse;
use Flatpack\Http\FlatpackResponseOptions;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Flatpack\Services\Lists\ListRecordsLoader;
use Flatpack\Services\Runtime\WidgetRuntime;
use Flatpack\Support\Exceptions\WidgetRuntimeException;
use Flatpack\Widgets\WidgetContext;
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
        private ListRecordsLoader $listRecordsLoader,
    ) {}

    public function index(Request $request): Response|JsonResponse
    {
        $debugLog = FlatpackResponse::compositionDebugLog(FlatpackResponse::compositionDebugContextForDashboard());

        /** @var array<string, mixed>|null $schema */
        $schema = $this->compositions->optional(
            Flatpack::dashboardEntity(),
            'list'
        );

        $normalizedWidgets = $this->widgetSchemaNormalizer->normalize($schema, $debugLog);
        $resolvedWidgets = $this->resolveWidgetData(
            $request,
            Flatpack::dashboardEntity(),
            $normalizedWidgets['widgets'] ?? [],
            $debugLog,
        );

        return FlatpackResponse::inertia(
            'dashboard',
            [
                'schema' => $schema,
                'widgets' => $resolvedWidgets,
            ],
            new FlatpackResponseOptions(compositionDebugLog: $debugLog),
        );
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
            $type = trim((string) ($definition['type'] ?? ''));
            $modelClass = trim((string) ($definition['model'] ?? ''));

            if ($providerKey === '' && ! ($type === 'table' && $modelClass !== '')) {
                $debugLog?->add(sprintf('widgets.%s ignored: missing provider.', $widgetId));

                continue;
            }

            if ($providerKey !== '') {
                try {
                    $provider = $this->widgetRuntime->resolveProvider($providerKey);
                    $data = $this->widgetRuntime->resolveData(
                        $provider,
                        new WidgetContext(
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
            } else {
                $data = $this->resolveModelBackedTableWidgetData($definition);
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
     * @return array<string, mixed>
     */
    private function normalizeResolvedWidgetData(array $definition, mixed $rawData): array
    {
        $data = is_array($rawData) ? $rawData : [];
        $type = isset($definition['type']) ? trim((string) $definition['type']) : '';
        if ($type === 'chart') {
            return $this->normalizeChartWidgetResolvedData($data);
        }
        if ($type === 'table') {
            return $this->normalizeTableWidgetResolvedData($data);
        }

        if ($type !== 'status') {
            return $data;
        }

        $status = $this->normalizeWidgetStatus($data['status'] ?? null);
        $data['status'] = $status ?? 'default';

        return $data;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function resolveModelBackedTableWidgetData(array $definition): array
    {
        $modelClass = trim((string) ($definition['model'] ?? ''));
        if ($modelClass === '') {
            return [];
        }

        $columns = $definition['columns'] ?? null;
        if (! is_array($columns) || $columns === []) {
            return [];
        }

        $schema = [
            'model' => $modelClass,
            'columns' => $columns,
        ];
        if (isset($definition['filters']) && is_array($definition['filters'])) {
            $schema['filters'] = $definition['filters'];
        }
        if (isset($definition['default_sort']) && is_array($definition['default_sort'])) {
            $schema['default_sort'] = $definition['default_sort'];
        }

        $pagination = is_array($definition['pagination'] ?? null) ? $definition['pagination'] : [];
        $perPage = isset($pagination['per_page']) && is_numeric($pagination['per_page'])
            ? max(1, (int) $pagination['per_page'])
            : 10;

        $loaded = $this->listRecordsLoader->load(
            $modelClass,
            $schema,
            1,
            $perPage,
        );

        return [
            'rows' => $loaded['records'] ?? [],
            'sorting' => $loaded['sorting'] ?? ['sort_by' => null, 'sort_direction' => null],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{rows: list<array<string, mixed>>, sorting: array{sort_by: string|null, sort_direction: 'asc'|'desc'|null}}
     */
    private function normalizeTableWidgetResolvedData(array $data): array
    {
        $rowsRaw = $data['rows'] ?? null;
        if (! is_array($rowsRaw)) {
            // Allow providers to return a plain list of rows directly.
            $rowsRaw = array_is_list($data) ? $data : [];
        }
        $rows = [];
        foreach ($rowsRaw as $row) {
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        $sorting = $data['sorting'] ?? null;
        $sortBy = null;
        $sortDirection = null;
        if (is_array($sorting)) {
            $candidate = trim((string) ($sorting['sort_by'] ?? ''));
            $direction = trim((string) ($sorting['sort_direction'] ?? ''));
            if ($candidate !== '') {
                $sortBy = $candidate;
            }
            if (in_array($direction, ['asc', 'desc'], true)) {
                $sortDirection = $direction;
            }
        }

        return [
            'rows' => $rows,
            'sorting' => [
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{points: list<array<string, mixed>>}
     */
    private function normalizeChartWidgetResolvedData(array $data): array
    {
        $points = $data['points'] ?? null;
        if (! is_array($points)) {
            return ['points' => []];
        }

        $clean = [];
        foreach ($points as $row) {
            if (is_array($row)) {
                $clean[] = $row;
            }
        }

        return ['points' => $clean];
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
