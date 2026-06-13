'use client';

import {
    Card,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { FlatpackCardWidget } from '@/types/widgets-composition';

type CardWidgetProps = {
    widget: FlatpackCardWidget;
};

export function CardWidget({ widget }: CardWidgetProps) {
    const context = widget.data?.context ?? '';
    const footer = widget.data?.footer ?? '';

    return (
        <Card className="@container/card">
            <CardHeader>
                <CardDescription>{widget.label}</CardDescription>
                <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                    {widget.data?.value ?? '—'}
                </CardTitle>
            </CardHeader>
            <CardFooter className="flex-col items-start gap-1.5 text-sm">
                <div className="w-full truncate font-medium">{context}</div>
                <div className="w-full truncate text-muted-foreground">
                    {footer}
                </div>
            </CardFooter>
        </Card>
    );
}
