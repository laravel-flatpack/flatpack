'use client';

import { CircleIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatAsRelativeTime } from '@/lib/relative-time';
import { normalizeWidgetStatus } from '@/lib/widget';
import type { FlatpackStatusWidget } from '@/types/widgets-composition';

type StatusWidgetProps = {
    widget: FlatpackStatusWidget;
};

export function StatusWidget({ widget }: StatusWidgetProps) {
    const status = normalizeWidgetStatus(widget.data?.status);
    const updatedAtLabel = formatAsRelativeTime(widget.data?.updated_at);
    const statusMeta = {
        default: {
            className: 'text-muted-foreground',
            label: 'Neutral',
        },
        info: {
            className:
                'border-blue-500/40 bg-blue-500/10 text-blue-700 dark:text-blue-300',
            label: 'Info',
        },
        success: {
            className:
                'border-green-500/40 bg-green-500/10 text-green-700 dark:text-green-300',
            label: 'Success',
        },
        warning: {
            className:
                'border-yellow-500/40 bg-yellow-500/10 text-yellow-700 dark:text-yellow-300',
            label: 'Warning',
        },
        error: {
            className:
                'border-red-500/40 bg-red-500/10 text-red-700 dark:text-red-300',
            label: 'Error',
        },
    }[status];

    const badgeTooltip = widget.data?.description?.trim() ?? '';

    const BadgeContent = () => (
        <Badge variant="outline" className={statusMeta.className}>
            <CircleIcon className="size-4 fill-current" />
            {statusMeta.label}
        </Badge>
    );

    return (
        <Card className="@container/card">
            <CardHeader>
                <div className="flex items-center justify-between gap-2">
                    <CardDescription>{widget.label}</CardDescription>
                    {badgeTooltip !== '' ? (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <BadgeContent />
                            </TooltipTrigger>
                            <TooltipContent>{badgeTooltip}</TooltipContent>
                        </Tooltip>
                    ) : (
                        <BadgeContent />
                    )}
                </div>
                <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                    {widget.data?.value ?? '—'}
                </CardTitle>
            </CardHeader>
            <CardFooter className="flex-col items-start gap-1.5 text-sm">
                <CardDescription>
                    {widget.data?.context ?? widget.data?.description ?? ''}
                </CardDescription>
                {updatedAtLabel !== '' ? (
                    <div className="w-full truncate text-muted-foreground">
                        Updated {updatedAtLabel}
                    </div>
                ) : null}
            </CardFooter>
        </Card>
    );
}
