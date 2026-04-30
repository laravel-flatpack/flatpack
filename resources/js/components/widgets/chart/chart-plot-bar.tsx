'use client';

import { Bar, BarChart, CartesianGrid, XAxis } from 'recharts';
import { ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import type { FlatpackChartConfig } from '@/types/widgets-composition';

type ChartPoint = Record<string, string | number | undefined>;

type ChartBarPlotProps = {
    data: ChartPoint[];
    xKey: string;
    series: FlatpackChartConfig['series'];
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

export function ChartBarPlot({ data, xKey, series }: ChartBarPlotProps) {
    return (
        <BarChart data={data} accessibilityLayer>
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
                <Bar
                    key={s.key}
                    dataKey={s.key}
                    fill={`var(--color-${s.key})`}
                    radius={4}
                />
            ))}
        </BarChart>
    );
}
