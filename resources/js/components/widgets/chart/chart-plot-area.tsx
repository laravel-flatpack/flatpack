'use client';

import { Area, AreaChart, CartesianGrid, XAxis } from 'recharts';
import { ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import type { FlatpackChartConfig } from '@/types/widgets-composition';

type ChartPoint = Record<string, string | number | undefined>;

type ChartAreaPlotProps = {
    data: ChartPoint[];
    xKey: string;
    series: FlatpackChartConfig['series'];
    variant: FlatpackChartConfig['variant'];
    gradientPrefix: string;
};

function formatXAxisValue(value: unknown): string {
    const date = new Date(String(value));
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}

export function ChartAreaPlot({
    data,
    xKey,
    series,
    variant,
    gradientPrefix,
}: ChartAreaPlotProps) {
    return (
        <AreaChart data={data} accessibilityLayer>
            <defs>
                {series.map((s) => (
                    <linearGradient
                        key={s.key}
                        id={`${gradientPrefix}-fill-${s.key}`}
                        x1="0"
                        y1="0"
                        x2="0"
                        y2="1"
                    >
                        <stop
                            offset="5%"
                            stopColor={`var(--color-${s.key})`}
                            stopOpacity={0.9}
                        />
                        <stop
                            offset="95%"
                            stopColor={`var(--color-${s.key})`}
                            stopOpacity={0.1}
                        />
                    </linearGradient>
                ))}
            </defs>
            <CartesianGrid vertical={false} />
            <XAxis
                dataKey={xKey}
                tickLine={false}
                axisLine={false}
                tickMargin={8}
                minTickGap={32}
                tickFormatter={formatXAxisValue}
            />
            <ChartTooltip
                cursor={false}
                content={
                    <ChartTooltipContent
                        labelFormatter={(value) => formatXAxisValue(value)}
                        indicator="dot"
                    />
                }
            />
            {series.map((s) => (
                <Area
                    key={s.key}
                    dataKey={s.key}
                    type="natural"
                    fill={`url(#${gradientPrefix}-fill-${s.key})`}
                    stroke={`var(--color-${s.key})`}
                    stackId={variant === 'area_stacked' ? 'stack' : undefined}
                />
            ))}
        </AreaChart>
    );
}
