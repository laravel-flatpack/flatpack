import type { ReactNode } from 'react';

type FlatpackPageHeaderProps = {
    title: string;
    subtitle?: string;
    actions?: ReactNode;
};

export function FlatpackPageHeader({
    title,
    subtitle,
    actions,
}: FlatpackPageHeaderProps) {
    return (
        <div className="flex w-full flex-col gap-2">
            <div className="flex w-full flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
                <div className="flex w-full flex-col gap-0">
                    <h1 className="min-w-0 flex-1 text-2xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    <div className="h-6 w-full">{subtitle}</div>
                </div>
                {actions ?? null}
            </div>
        </div>
    );
}
