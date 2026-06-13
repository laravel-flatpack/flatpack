import type {
    FlatpackDataTableBulkAction,
    FlatpackDataTableColumn,
    FlatpackDataTableDefaultSort,
    FlatpackListServerPagination,
    FlatpackListServerSorting,
} from '@/types/data-table';
import type { FieldSpanNamed } from '@/types/form-fields';

export type FlatpackMetricValueFormat = {
    kind: 'number' | 'currency' | 'percentage';
    currency?: string;
    minimumFractionDigits?: number;
    maximumFractionDigits?: number;
};

export type FlatpackMetricPeriod = {
    kind: 'day' | 'week' | 'month' | 'quarter' | 'year' | 'custom';
    lookback?: number;
    label?: string;
};

export type FlatpackMetricTrendOptions = {
    precision?: number;
};

export type FlatpackCardWidgetStatus =
    typeof import('@/lib/generated/composition-schema-keys').WIDGET_STATUS_VALUES[number];

export type FlatpackStatusWidgetStatus =
    typeof import('@/lib/generated/composition-schema-keys').WIDGET_STATUS_VALUES[number];

export type FlatpackStatusWidgetResolvedData = {
    status?: FlatpackStatusWidgetStatus;
    value?: number | string;
    context?: string;
    updated_at?: string;
    description?: string;
};

export type FlatpackMetricWidgetResolvedData = {
    value?: number;
    trend?: {
        direction?: 'up' | 'down' | 'flat';
        percent?: number;
        comment?: string;
    };
    description?: string;
};

/** Same string union as JSON `optionStatus` / select option badge `status`. */
export type FlatpackChartSeriesColorToken =
    typeof import('@/lib/generated/composition-schema-keys').OPTION_STATUS_VALUES[number];

export type FlatpackChartSeries = {
    key: string;
    label: string;
    /** Option-status semantic token or raw CSS (`var(…)`, `#rgb`, …). */
    color?: FlatpackChartSeriesColorToken | string;
};

export type FlatpackChartTimeRange = {
    id: string;
    label: string;
};

export type FlatpackChartConfig = {
    x_key: string;
    mode?: 'area' | 'bar' | 'line';
    variant: 'area_stacked' | 'area';
    series: FlatpackChartSeries[];
    time_ranges?: FlatpackChartTimeRange[];
};

export type FlatpackChartWidgetResolvedData = {
    points?: Array<Record<string, string | number | undefined>>;
};

export type FlatpackMetricWidget = {
    type: 'metric';
    provider: string;
    label: string;
    description?: string | null;
    value_format: FlatpackMetricValueFormat;
    period: FlatpackMetricPeriod;
    trend?: FlatpackMetricTrendOptions | null;
    data?: FlatpackMetricWidgetResolvedData;
    span?: FieldSpanNamed;
};

export type FlatpackCardWidget = {
    type: 'card';
    provider: string;
    label: string;
    data?: {
        value?: number | string;
        context?: string;
        footer?: string;
    };
    span?: FieldSpanNamed;
};

export type FlatpackStatusWidget = {
    type: 'status';
    provider: string;
    label: string;
    description?: string | null;
    data?: FlatpackStatusWidgetResolvedData;
    span?: FieldSpanNamed;
};

export type FlatpackChartWidget = {
    type: 'chart';
    provider: string;
    label: string;
    description?: string | null;
    chart: FlatpackChartConfig;
    data?: FlatpackChartWidgetResolvedData;
    span?: FieldSpanNamed;
};

export type FlatpackTableWidgetResolvedData = {
    rows?: Record<string, unknown>[];
    /** Model-backed only: echo of the server-side search filter for this widget. */
    search?: string;
    /** Model-backed only: server-driven sort metadata for query-string round-trips. */
    sorting?: FlatpackListServerSorting;
    /** Model-backed only: server-driven pagination metadata for query-string round-trips. */
    pagination?: FlatpackListServerPagination;
};

export type FlatpackTableWidget = {
    type: 'table';
    label?: string | null;
    description?: string | null;
    icon?: string | null;
    provider?: string;
    model?: string;
    /**
     * Flatpack entity slug for toolbar list actions (`create`, …). Set `entity` / `list_entity`
     * in YAML or rely on server inference from list compositions matching {@link model}.
     */
    list_entity?: string | null;
    /**
     * Toolbar buttons (e.g. Create). Accepts the same map/array shapes as embedded form tables.
     */
    actions?: unknown;
    /**
     * Model-backed: columns are required in YAML.
     * Provider-backed: columns are optional in YAML; when present they take precedence
     * over any columns returned at runtime by the widget provider.
     */
    columns?: Record<string, FlatpackDataTableColumn>;
    showColumnsVisibility?: boolean;
    bulk_actions?: FlatpackDataTableBulkAction[];
    pagination?: boolean | { per_page?: number; page_sizes?: number[] };
    /**
     * Model-backed: server page size when `pagination.per_page` is not set (default 5 rows).
     * Omitted in provider-backed widgets.
     */
    paginate?: number;
    default_sort?: FlatpackDataTableDefaultSort;
    data?: FlatpackTableWidgetResolvedData;
    span?: FieldSpanNamed;
};

/**
 * Optional card slot map for `type: grid` widgets. Each value is a column id on the same widget
 * (referencing entries in {@link FlatpackGridWidget.columns}). When omitted, slots auto-derive
 * from columns: first text-like column becomes the title, badge/status columns become badges,
 * other non-action columns stack as label/value pairs in the card body, and an `actions` column
 * (if any) renders as the card footer.
 */
export type FlatpackGridCardSlotMap = {
    title?: string;
    subtitle?: string;
    image?: string;
    badges?: string[];
    body?: string[];
    footer_actions?: string;
};

/**
 * Grid widget: same option surface as {@link FlatpackTableWidget} (model XOR provider, columns,
 * actions, bulk actions, filters, pagination, default sort) but rendered as a responsive card
 * grid instead of a table body. The optional `card` slot map lets authors map columns to card
 * regions; otherwise slots auto-derive from `columns`.
 */
export type FlatpackGridWidget = {
    type: 'grid';
    label?: string | null;
    description?: string | null;
    icon?: string | null;
    provider?: string;
    model?: string;
    list_entity?: string | null;
    actions?: unknown;
    columns?: Record<string, FlatpackDataTableColumn>;
    showColumnsVisibility?: boolean;
    bulk_actions?: FlatpackDataTableBulkAction[];
    pagination?: boolean | { per_page?: number; page_sizes?: number[] };
    /**
     * Model-backed: server page size when `pagination.per_page` is not set (default 6 cards).
     * Omitted in provider-backed widgets.
     */
    paginate?: number;
    default_sort?: FlatpackDataTableDefaultSort;
    data?: FlatpackTableWidgetResolvedData;
    card?: FlatpackGridCardSlotMap;
    span?: FieldSpanNamed;
};

export type FlatpackWidget =
    | FlatpackMetricWidget
    | FlatpackCardWidget
    | FlatpackStatusWidget
    | FlatpackChartWidget
    | FlatpackTableWidget
    | FlatpackGridWidget;

export type FlatpackWidgetTabPanelLayout = {
    id: string;
    label: string;
    icon?: string;
    widget_ids: string[];
};

export type FlatpackWidgetsCompositionSchema = {
    widgets: Record<string, FlatpackWidget>;
    tab_panels?: FlatpackWidgetTabPanelLayout[];
};
