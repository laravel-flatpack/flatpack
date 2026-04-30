import type {
    FlatpackDataTableBulkAction,
    FlatpackDataTableColumn,
    FlatpackDataTableDefaultSort,
} from '@/types/data-table';

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

export type FlatpackCardWidgetResolvedData = {
    status?: FlatpackCardWidgetStatus;
    value?: number | string;
    context?: string;
    updated_at?: string;
    description?: string;
};

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
};

export type FlatpackStatusWidget = {
    type: 'status';
    provider: string;
    label: string;
    description?: string | null;
    data?: FlatpackStatusWidgetResolvedData;
};

export type FlatpackChartWidget = {
    type: 'chart';
    provider: string;
    label: string;
    description?: string | null;
    chart: FlatpackChartConfig;
    data?: FlatpackChartWidgetResolvedData;
};

export type FlatpackTableWidgetResolvedData = {
    rows?: Record<string, unknown>[];
    sorting?: {
        sort_by?: string | null;
        sort_direction?: 'asc' | 'desc' | null;
    };
};

export type FlatpackTableWidget = {
    type: 'table';
    label?: string | null;
    description?: string | null;
    icon?: string | null;
    provider?: string;
    model?: string;
    columns: Record<string, FlatpackDataTableColumn>;
    bulk_actions?: FlatpackDataTableBulkAction[];
    pagination?: boolean | { per_page?: number; page_sizes?: number[] };
    default_sort?: FlatpackDataTableDefaultSort;
    data?: FlatpackTableWidgetResolvedData;
};

export type FlatpackWidget =
    | FlatpackMetricWidget
    | FlatpackCardWidget
    | FlatpackStatusWidget
    | FlatpackChartWidget
    | FlatpackTableWidget;

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
