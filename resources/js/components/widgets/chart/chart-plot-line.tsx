'use client';

import { CartesianGrid, Line, LineChart, XAxis } from 'recharts';
import { ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import type { FlatpackChartConfig } from '@/types/widgets-composition';

type ChartPoint = Record<string, string | number | undefined>;

type ChartLinePlotProps = {
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

export function ChartLinePlot({ data, xKey, series }: ChartLinePlotProps) {
    return (
        <LineChart data={data} accessibilityLayer>
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
                <Line
                    key={s.key}
                    dataKey={s.key}
                    stroke={`var(--color-${s.key})`}
                    dot={false}
                    strokeWidth={2}
                />
            ))}
        </LineChart>
    );
}
