'use client';

import { CircleIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { normalizeWidgetStatus } from '@/lib/widget';
import type { FlatpackCardWidget } from '@/types/widgets-composition';

type CardWidgetProps = {
    widget: FlatpackCardWidget;
};

export function CardWidget({ widget }: CardWidgetProps) {
    const status = normalizeWidgetStatus(widget.data?.status);
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
        <Card className="@container/card md:col-span-2">
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
                <CardTitle className="text-xl font-semibold @[250px]/card:text-2xl">
                    {widget.data?.value ?? '—'}
                </CardTitle>
                <CardDescription>
                    {widget.data?.context ?? widget.data?.description ?? ''}
                </CardDescription>
                {widget.data?.updated_at ? (
                    <CardDescription>
                        Updated {widget.data.updated_at}
                    </CardDescription>
                ) : null}
            </CardHeader>
        </Card>
    );
}
