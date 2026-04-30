'use client';

import * as React from 'react';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { type ChartConfig, ChartContainer } from '@/components/ui/chart';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useIsMobile } from '@/hooks/use-mobile';
import { OPTION_STATUS_VALUES } from '@/lib/generated/composition-schema-keys';
import type {
    FlatpackChartConfig,
    FlatpackChartSeriesColorToken,
    FlatpackChartTimeRange,
    FlatpackChartWidget,
} from '@/types/widgets-composition';

export const description = 'Configurable multi-series chart (area, bar, line)';

const DEFAULT_CHART_COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
] as const;

/** Maps YAML `optionStatus` tokens to theme CSS variables (chart fills/strokes). */
const CHART_OPTION_STATUS_COLORS: Record<
    FlatpackChartSeriesColorToken,
    string
> = {
    success: 'var(--color-success)',
    error: 'var(--color-destructive)',
    warning: 'var(--color-highlight)',
    info: 'var(--color-brand)',
    pending: 'var(--color-chart-2)',
};

function sanitizeCssFragment(id: string): string {
    return id.replace(/[^a-zA-Z0-9_-]/g, '');
}

function parseDaysFromRangeId(id: string): number {
    const m = /^(\d+)d$/i.exec(id.trim());
    if (m) {
        return Math.max(1, parseInt(m[1], 10));
    }
    return 90;
}

function defaultRangeId(
    timeRanges: FlatpackChartTimeRange[],
    isMobile: boolean,
): string {
    if (isMobile) {
        const week = timeRanges.find((r) => r.id === '7d');
        if (week) {
            return week.id;
        }
    }
    return timeRanges[0]?.id ?? '90d';
}

function resolveSeriesColor(
    raw: string | undefined,
    seriesIndex: number,
): string {
    if (raw == null || raw.trim() === '') {
        return DEFAULT_CHART_COLORS[seriesIndex % DEFAULT_CHART_COLORS.length];
    }
    const token = raw.trim();
    if ((OPTION_STATUS_VALUES as readonly string[]).includes(token)) {
        return CHART_OPTION_STATUS_COLORS[
            token as FlatpackChartSeriesColorToken
        ];
    }
    return token;
}

function buildChartConfig(series: FlatpackChartConfig['series']): ChartConfig {
    const config: ChartConfig = {};
    for (let i = 0; i < series.length; i++) {
        const s = series[i];
        if (s == null) {
            continue;
        }
        config[s.key] = {
            label: s.label,
            color: resolveSeriesColor(s.color, i),
        };
    }
    return config;
}

type ChartWidgetProps = {
    widget: FlatpackChartWidget;
};

const ChartAreaPlot = React.lazy(() =>
    import('@/components/widgets/chart/chart-plot-area').then((module) => ({
        default: module.ChartAreaPlot,
    })),
);

const ChartBarPlot = React.lazy(() =>
    import('@/components/widgets/chart/chart-plot-bar').then((module) => ({
        default: module.ChartBarPlot,
    })),
);

const ChartLinePlot = React.lazy(() =>
    import('@/components/widgets/chart/chart-plot-line').then((module) => ({
        default: module.ChartLinePlot,
    })),
);

export function ChartWidget({ widget }: ChartWidgetProps) {
    const isMobile = useIsMobile();
    const chart = widget.chart;
    const timeRanges = chart.time_ranges ?? [];
    const hasRangeControls = timeRanges.length > 0;
    const points = widget.data?.points ?? [];
    const xKey = chart.x_key;
    const series = chart.series;
    const mode =
        chart.mode === 'bar' || chart.mode === 'line' ? chart.mode : 'area';
    const variant = chart.variant;
    const gradientPrefix = sanitizeCssFragment(React.useId());

    const referenceDate = React.useMemo(() => {
        let max: Date | null = null;
        for (const row of points) {
            const raw = row[xKey];
            if (typeof raw !== 'string' && typeof raw !== 'number') {
                continue;
            }
            const d = new Date(String(raw));
            if (!Number.isNaN(d.getTime())) {
                if (max === null || d > max) {
                    max = d;
                }
            }
        }
        return max ?? new Date();
    }, [points, xKey]);

    const [timeRange, setTimeRange] = React.useState(() =>
        hasRangeControls ? defaultRangeId(timeRanges, isMobile) : '',
    );

    React.useEffect(() => {
        setTimeRange(
            hasRangeControls ? defaultRangeId(timeRanges, isMobile) : '',
        );
    }, [timeRanges, isMobile, hasRangeControls]);

    const chartConfig = React.useMemo(() => buildChartConfig(series), [series]);

    const filteredPoints = React.useMemo(() => {
        if (!hasRangeControls) {
            return points;
        }
        const days = parseDaysFromRangeId(timeRange);
        const startDate = new Date(referenceDate);
        startDate.setDate(startDate.getDate() - days);

        return points.filter((row) => {
            const raw = row[xKey];
            if (typeof raw !== 'string' && typeof raw !== 'number') {
                return false;
            }
            const date = new Date(String(raw));
            if (Number.isNaN(date.getTime())) {
                return false;
            }
            return date >= startDate;
        });
    }, [points, xKey, timeRange, referenceDate, hasRangeControls]);

    const filteredRangeEmpty = points.length > 0 && filteredPoints.length === 0;

    const rangeLabelById = React.useMemo(() => {
        const m = new Map<string, string>();
        for (const r of timeRanges) {
            m.set(r.id, r.label);
        }
        return m;
    }, [timeRanges]);

    const firstRangeLabel = timeRanges[0]?.label ?? 'Range';

    if (series.length === 0) {
        return (
            <Card className="@container/card">
                <CardHeader>
                    <CardTitle>{widget.label}</CardTitle>
                    {widget.description != null && widget.description !== '' ? (
                        <CardDescription>{widget.description}</CardDescription>
                    ) : null}
                </CardHeader>
                <CardContent className="text-sm text-muted-foreground">
                    Chart series are not configured.
                </CardContent>
            </Card>
        );
    }

    if (points.length === 0) {
        return (
            <Card className="@container/card">
                <CardHeader>
                    <CardTitle>{widget.label}</CardTitle>
                    {widget.description != null && widget.description !== '' ? (
                        <CardDescription>{widget.description}</CardDescription>
                    ) : null}
                </CardHeader>
                <CardContent className="text-sm text-muted-foreground">
                    No data available for this chart.
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="@container/card">
            <CardHeader>
                <CardTitle>{widget.label}</CardTitle>
                <CardDescription>
                    {widget.description != null && widget.description !== '' ? (
                        <>
                            <span className="hidden @[540px]/card:block">
                                {widget.description}
                            </span>
                            <span className="@[540px]/card:hidden line-clamp-2">
                                {widget.description}
                            </span>
                        </>
                    ) : (
                        <>
                            <span className="hidden @[540px]/card:block">
                                {firstRangeLabel}
                            </span>
                            <span className="@[540px]/card:hidden">
                                {firstRangeLabel}
                            </span>
                        </>
                    )}
                </CardDescription>
                <CardAction>
                    {hasRangeControls ? (
                        <>
                            <ToggleGroup
                                type="single"
                                value={timeRange}
                                onValueChange={(v) => {
                                    if (v !== '') {
                                        setTimeRange(v);
                                    }
                                }}
                                variant="outline"
                                className="hidden *:data-[slot=toggle-group-item]:px-4! @[767px]/card:flex"
                            >
                                {timeRanges.map((r) => (
                                    <ToggleGroupItem key={r.id} value={r.id}>
                                        {r.label}
                                    </ToggleGroupItem>
                                ))}
                            </ToggleGroup>
                            <Select
                                value={timeRange}
                                onValueChange={(v) => {
                                    if (v !== '') {
                                        setTimeRange(v);
                                    }
                                }}
                            >
                                <SelectTrigger
                                    className="flex w-40 **:data-[slot=select-value]:block **:data-[slot=select-value]:truncate @[767px]/card:hidden"
                                    size="sm"
                                    aria-label="Select range"
                                >
                                    <SelectValue
                                        placeholder={rangeLabelById.get(
                                            timeRange,
                                        )}
                                    />
                                </SelectTrigger>
                                <SelectContent className="rounded-xl">
                                    {timeRanges.map((r) => (
                                        <SelectItem
                                            key={r.id}
                                            value={r.id}
                                            className="rounded-lg"
                                        >
                                            {r.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </>
                    ) : null}
                </CardAction>
            </CardHeader>
            <CardContent className="px-2 pt-4 sm:px-6 sm:pt-6">
                {filteredRangeEmpty ? (
                    <p className="py-12 text-center text-sm text-muted-foreground">
                        No data in the selected range.
                    </p>
                ) : null}
                <ChartContainer
                    config={chartConfig}
                    className={`aspect-auto h-[250px] w-full ${filteredRangeEmpty ? 'hidden' : ''}`}
                >
                    <React.Suspense
                        fallback={
                            <div className="h-[250px] w-full animate-pulse rounded-xl border border-border bg-muted/40" />
                        }
                    >
                        {mode === 'bar' ? (
                            <ChartBarPlot
                                data={filteredPoints}
                                xKey={xKey}
                                series={series}
                            />
                        ) : null}
                        {mode === 'line' ? (
                            <ChartLinePlot
                                data={filteredPoints}
                                xKey={xKey}
                                series={series}
                            />
                        ) : null}
                        {mode === 'area' ? (
                            <ChartAreaPlot
                                data={filteredPoints}
                                xKey={xKey}
                                series={series}
                                variant={variant}
                                gradientPrefix={gradientPrefix}
                            />
                        ) : null}
                    </React.Suspense>
                </ChartContainer>
            </CardContent>
        </Card>
    );
}
