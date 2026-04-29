'use client';

import { TrendingDownIcon, TrendingUpIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardAction,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    formatMetricValue,
    formatTrendPercent,
    metricTrendComment,
} from '@/lib/widget';
import type { FlatpackMetricWidget } from '@/types/widgets-composition';

type MetricCardProps = {
    widget: FlatpackMetricWidget;
};

export function MetricWidget({ widget }: MetricCardProps) {
    const direction = widget.data?.trend?.direction ?? 'flat';
    const isDown = direction === 'down';

    return (
        <Card className="@container/card">
            <CardHeader>
                <CardDescription>{widget.label}</CardDescription>
                <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                    {formatMetricValue(widget)}
                </CardTitle>
                <CardAction>
                    <Badge variant="outline">
                        {isDown ? (
                            <TrendingDownIcon className="text-destructive" />
                        ) : (
                            <TrendingUpIcon className="text-success" />
                        )}
                        {formatTrendPercent(widget)}
                    </Badge>
                </CardAction>
            </CardHeader>
            <CardFooter className="flex-col items-start gap-1.5 text-sm">
                <div className="line-clamp-1 flex gap-2 font-medium">
                    {metricTrendComment(widget)}{' '}
                    {isDown ? (
                        <TrendingDownIcon className="size-4 text-destructive" />
                    ) : (
                        <TrendingUpIcon className="size-4 text-success" />
                    )}
                </div>
                <div className="text-muted-foreground">
                    {widget.description ?? widget.data?.description ?? ''}
                </div>
            </CardFooter>
        </Card>
    );
}
