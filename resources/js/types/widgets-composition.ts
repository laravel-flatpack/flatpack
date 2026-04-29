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

export type FlatpackMetricWidgetResolvedData = {
    value?: number;
    trend?: {
        direction?: 'up' | 'down' | 'flat';
        percent?: number;
        comment?: string;
    };
    description?: string;
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
    data?: FlatpackCardWidgetResolvedData;
};

export type FlatpackWidget = FlatpackMetricWidget | FlatpackCardWidget;

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
