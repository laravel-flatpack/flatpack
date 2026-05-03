<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Facades\Flatpack;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Flatpack\Services\Lists\ListQueryParams;
use Flatpack\Services\Lists\ListRecordsLoader;
use Flatpack\Services\Runtime\WidgetRuntime;
use Flatpack\Support\CompositionDebugContext;
use Flatpack\Support\Exceptions\WidgetRuntimeException;
use Flatpack\Widgets\WidgetContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

trait ResolvesWidgets
{
    private const MODEL_BACKED_TABLE_WIDGET_DEFAULT_PER_PAGE = 5;

    private const MODEL_BACKED_GRID_WIDGET_DEFAULT_PER_PAGE = 6;

    abstract private function widgetSchemaNormalizer(): WidgetSchemaNormalizer;

    abstract private function widgetRuntime(): WidgetRuntime;

    abstract private function listRecordsLoader(): ListRecordsLoader;

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    private function normalizedWidgetsSchema(?array $schema): ?array
    {
        return $this->widgetSchemaNormalizer()->normalize($schema);
    }

    /**
     * Resolves widget payloads only when the normalized schema defines at least one widget.
     * Skips provider/model resolution entirely when there is nothing to render (avoids eager
     * {@see ListRecordsLoader} work for model-backed table widgets on widget-less pages).
     *
     * @param  array<string, mixed>  $widgets
     * @return array<string, array<string, mixed>>
     */
    private function resolveWidgetDataWhenPresent(
        Request $request,
        string $entity,
        array $widgets,
    ): array {
        if ($widgets === []) {
            return [];
        }

        return $this->resolveWidgetData($request, $entity, $widgets);
    }

    /**
     * @param  array<string, mixed>  $widgets
     * @return array<string, array<string, mixed>>
     */
    private function resolveWidgetData(
        Request $request,
        string $entity,
        array $widgets,
    ): array {
        $resolved = [];
        $compositionDebug = app(CompositionDebugContext::class);

        foreach ($widgets as $widgetId => $definition) {
            if (! is_string($widgetId) || ! is_array($definition)) {
                continue;
            }

            $providerKey = trim((string) ($definition['provider'] ?? ''));
            $type = trim((string) ($definition['type'] ?? ''));
            $modelClass = trim((string) ($definition['model'] ?? ''));
            $isTableLike = $this->isTableLikeWidgetType($type);

            if ($providerKey === '' && ! ($isTableLike && $modelClass !== '')) {
                $compositionDebug->add(sprintf('widgets.%s ignored: missing provider.', $widgetId));

                continue;
            }

            if ($providerKey !== '') {
                try {
                    $provider = $this->widgetRuntime()->resolveProvider($providerKey);
                    $data = $this->widgetRuntime()->resolveData(
                        $provider,
                        new WidgetContext(
                            request: $request,
                            entity: $entity,
                            widgetId: $widgetId,
                            definition: $definition,
                        ),
                    );
                } catch (WidgetRuntimeException $exception) {
                    $compositionDebug->add(sprintf('widgets.%s provider error: %s', $widgetId, $exception->getMessage()));
                    $data = [];
                } catch (AuthorizationException $exception) {
                    $compositionDebug->add(sprintf('widgets.%s unauthorized: %s', $widgetId, $exception->getMessage()));
                    $data = [];
                }
            } else {
                $data = $this->resolveModelBackedTableWidgetData($request, $widgetId, $definition);
            }

            $entry = [...$definition];
            if ($isTableLike && $providerKey !== '' && array_key_exists('columns', $data)) {
                $columnPayload = $data['columns'];
                unset($data['columns']);
                if (isset($entry['columns']) && is_array($entry['columns']) && $entry['columns'] !== []) {
                    $compositionDebug->add(sprintf(
                        'widgets.%s: ignoring provider-returned columns; YAML columns take precedence.',
                        $widgetId,
                    ));
                } else {
                    $entry['columns'] = $this->widgetSchemaNormalizer()->normalizeProviderResolvedTableColumns($columnPayload);
                }
            }
            if ($isTableLike && $providerKey !== '' && ! isset($entry['columns'])) {
                $entry['columns'] = [];
            }

            $entry['data'] = $this->normalizeResolvedWidgetData($definition, $data);

            $resolved[$widgetId] = $entry;
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
        if ($this->isTableLikeWidgetType($type)) {
            $hasProvider = trim((string) ($definition['provider'] ?? '')) !== '';

            return $hasProvider
                ? $this->normalizeProviderTableWidgetResolvedData($data)
                : $this->normalizeModelTableWidgetResolvedData($data, $definition);
        }

        if ($type !== 'status') {
            return $data;
        }

        $status = $this->widgetSchemaNormalizer()->normalizeWidgetStatusValue($data['status'] ?? null);
        $data['status'] = $status ?? 'default';

        return $data;
    }

    /**
     * Table and grid widgets share runtime: the same provider/model resolution paths and
     * the same row/sorting/pagination payload shape. Only the React renderer differs.
     */
    private function isTableLikeWidgetType(string $type): bool
    {
        return $type === 'table' || $type === 'grid';
    }

    /**
     * Provider-backed table widgets are snapshots: only `rows` is produced; pagination/sorting/search
     * are client-side concerns and never round-trip through the resolved data.
     *
     * @param  array<string, mixed>  $data
     * @return array{rows: list<array<string, mixed>>}
     */
    private function normalizeProviderTableWidgetResolvedData(array $data): array
    {
        $rowsRaw = $data['rows'] ?? null;
        if (! is_array($rowsRaw)) {
            $rowsRaw = array_is_list($data) ? $data : [];
        }
        $rows = [];
        foreach ($rowsRaw as $row) {
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        return ['rows' => $rows];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     sorting: array{sort_by: string|null, sort_direction: 'asc'|'desc'|null},
     *     pagination: array{
     *         current_page: int,
     *         last_page: int,
     *         per_page: int,
     *         total: int,
     *         from: int|null,
     *         to: int|null,
     *     },
     *     search: string,
     * }
     */
    private function resolveModelBackedTableWidgetData(Request $request, string $widgetId, array $definition): array
    {
        $modelClass = trim((string) ($definition['model'] ?? ''));
        if ($modelClass === '') {
            return $this->emptyModelBackedTableWidgetPayload(
                $this->modelBackedWidgetDefaultPerPage($definition),
            );
        }

        $columns = $definition['columns'] ?? null;
        if (! is_array($columns) || $columns === []) {
            return $this->emptyModelBackedTableWidgetPayload(
                $this->modelBackedWidgetSchemaPerPage($definition),
            );
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

        $schemaPerPage = $this->modelBackedWidgetSchemaPerPage($definition);

        $perPage = $this->widgetTablePerPageFromRequest($request, $widgetId, $schemaPerPage);
        $page = $this->widgetTablePageFromRequest($request, $widgetId);
        $search = $this->widgetTableSearchFromRequest($request, $widgetId);
        [$sortBy, $sortDirection] = $this->widgetTableSortFromRequest($request, $widgetId);

        $loaded = $this->listRecordsLoader()->load(
            $modelClass,
            $schema,
            new ListQueryParams(
                page: $page,
                perPage: $perPage,
                search: $search,
                filters: [],
                sortBy: $sortBy,
                sortDirection: $sortDirection,
            ),
        );

        return [
            'rows' => $loaded['records'] ?? [],
            'sorting' => $loaded['sorting'] ?? ['sort_by' => null, 'sort_direction' => null],
            'pagination' => is_array($loaded['pagination'] ?? null)
                ? $loaded['pagination']
                : [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'from' => null,
                    'to' => null,
                ],
            'search' => $search ?? '',
        ];
    }

    /**
     * Default server page size when YAML omits `pagination.per_page` and `paginate` (grid: 6, table: 5).
     *
     * @param  array<string, mixed>  $definition
     */
    private function modelBackedWidgetDefaultPerPage(array $definition): int
    {
        return trim((string) ($definition['type'] ?? '')) === 'grid'
            ? self::MODEL_BACKED_GRID_WIDGET_DEFAULT_PER_PAGE
            : self::MODEL_BACKED_TABLE_WIDGET_DEFAULT_PER_PAGE;
    }

    /**
     * Page size from YAML for model-backed table/grid widgets: `pagination.per_page` when the
     * pagination value is an object with `per_page`, else top-level `paginate`, else the type default.
     *
     * @param  array<string, mixed>  $definition
     */
    private function modelBackedWidgetSchemaPerPage(array $definition): int
    {
        $paginationYaml = is_array($definition['pagination'] ?? null) ? $definition['pagination'] : [];
        if (isset($paginationYaml['per_page']) && is_numeric($paginationYaml['per_page'])) {
            return max(1, (int) $paginationYaml['per_page']);
        }
        if (isset($definition['paginate']) && is_numeric($definition['paginate'])) {
            return max(1, (int) $definition['paginate']);
        }

        return $this->modelBackedWidgetDefaultPerPage($definition);
    }

    private function emptyModelBackedTableWidgetPayload(int $perPage): array
    {
        $perPage = max(1, $perPage);

        return [
            'rows' => [],
            'sorting' => ['sort_by' => null, 'sort_direction' => null],
            'pagination' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
                'from' => null,
                'to' => null,
            ],
            'search' => '',
        ];
    }

    private function widgetTablePageFromRequest(Request $request, string $widgetId): int
    {
        $named = $request->query(sprintf('%s_page', $widgetId));
        if (is_numeric($named)) {
            return max(1, (int) $named);
        }

        return 1;
    }

    /**
     * @return string|null Search term for {@see ListRecordsLoader} (null = no search filter).
     */
    private function widgetTableSearchFromRequest(Request $request, string $widgetId): ?string
    {
        $named = $request->query(sprintf('%s_q', $widgetId));
        if (is_string($named)) {
            $term = trim($named);

            return $term === '' ? null : $term;
        }

        return null;
    }

    /**
     * @return array{0: string|null, 1: 'asc'|'desc'}
     */
    private function widgetTableSortFromRequest(Request $request, string $widgetId): array
    {
        $namedBy = $request->query(sprintf('%s_sort_by', $widgetId));
        $namedDir = $request->query(sprintf('%s_sort_dir', $widgetId));
        if ($namedBy !== null || $namedDir !== null) {
            $by = is_string($namedBy) ? trim($namedBy) : '';
            $dirRaw = is_string($namedDir) ? trim($namedDir) : '';
            $dir = $dirRaw === 'asc' ? 'asc' : 'desc';

            return [$by === '' ? null : $by, $dir];
        }

        return [null, 'desc'];
    }

    private function widgetTablePerPageFromRequest(Request $request, string $widgetId, int $schemaPerPage): int
    {
        $maxPerPage = Flatpack::maxListPerPage();
        $schemaPerPage = max(1, min($maxPerPage, $schemaPerPage));
        $named = $request->query(sprintf('%s_limit', $widgetId));
        if (is_numeric($named)) {
            return max(1, min($maxPerPage, (int) $named));
        }

        return $schemaPerPage;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $definition
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     sorting: array{sort_by: string|null, sort_direction: 'asc'|'desc'|null},
     *     pagination: array{
     *         current_page: int,
     *         last_page: int,
     *         per_page: int,
     *         total: int,
     *         from: int|null,
     *         to: int|null,
     *     },
     * }
     */
    private function normalizeModelTableWidgetResolvedData(array $data, array $definition): array
    {
        $defaultPerPage = $this->modelBackedWidgetDefaultPerPage($definition);
        $rowsRaw = $data['rows'] ?? null;
        if (! is_array($rowsRaw)) {
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

        $rawPagination = $data['pagination'] ?? null;
        $pagination = [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => $defaultPerPage,
            'total' => 0,
            'from' => null,
            'to' => null,
        ];
        if (is_array($rawPagination)) {
            $pagination['current_page'] = max(1, (int) ($rawPagination['current_page'] ?? 1));
            $pagination['last_page'] = max(1, (int) ($rawPagination['last_page'] ?? 1));
            $pagination['per_page'] = max(1, (int) ($rawPagination['per_page'] ?? $defaultPerPage));
            $pagination['total'] = max(0, (int) ($rawPagination['total'] ?? 0));
            $from = $rawPagination['from'] ?? null;
            $to = $rawPagination['to'] ?? null;
            $pagination['from'] = is_numeric($from) ? (int) $from : null;
            $pagination['to'] = is_numeric($to) ? (int) $to : null;
        }

        $search = $data['search'] ?? null;
        $searchOut = is_string($search) ? $search : '';

        return [
            'rows' => $rows,
            'sorting' => [
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
            'pagination' => $pagination,
            'search' => $searchOut,
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
}
