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

    return (
        <Card className="@container/card">
            <CardHeader>
                <CardDescription className="truncate">
                    {widget.label}
                </CardDescription>
                <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                    {formatMetricValue(widget)}
                </CardTitle>
                <CardAction>
                    {direction ? (
                        <Badge variant="outline">
                            {direction === 'down' && (
                                <TrendingDownIcon className="text-destructive" />
                            )}
                            {direction === 'up' && (
                                <TrendingUpIcon className="text-success" />
                            )}
                            {formatTrendPercent(widget)}
                        </Badge>
                    ) : null}
                </CardAction>
            </CardHeader>
            <CardFooter className="flex-col items-start gap-1.5 text-sm">
                <div className="line-clamp-1 truncate flex gap-2 font-medium">
                    {metricTrendComment(widget)}{' '}
                    {direction === 'down' && (
                        <TrendingDownIcon className="size-4 text-destructive" />
                    )}
                    {direction === 'up' && (
                        <TrendingUpIcon className="size-4 text-success" />
                    )}
                </div>
                <div className="w-full truncate text-muted-foreground">
                    {widget.description ?? widget.data?.description ?? ''}
                </div>
            </CardFooter>
        </Card>
    );
}
