import { type ReactNode, useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';
import type { FlatpackBreadcrumb } from '@/types/flatpack';

type PageHeaderProps = {
    title: string;
    subtitle?: string;
    actions?: ReactNode;
    breadcrumbs?: FlatpackBreadcrumb[];
    /** When true, wraps header content in the standard page gutter (padding + vertical rhythm). */
    withPaddingShell?: boolean;
    /** Sidebar trigger + breadcrumbs (use `TopToolbar`); shown only while this header is stuck on scroll. */
    stickyTitle?: ReactNode;
};

export function PageHeader({
    title,
    subtitle,
    actions,
    withPaddingShell = false,
    stickyTitle,
}: PageHeaderProps) {
    const sentinelRef = useRef<HTMLDivElement>(null);
    const [isStuck, setIsStuck] = useState(false);

    const hasStickyToolbar = stickyTitle != null;

    useEffect(() => {
        if (!hasStickyToolbar) {
            return;
        }
        const sentinel = sentinelRef.current;
        if (sentinel === null) {
            return;
        }
        const observer = new IntersectionObserver(
            ([entry]) => {
                setIsStuck(!entry.isIntersecting);
            },
            { root: null, threshold: 0 },
        );
        observer.observe(sentinel);
        return () => observer.disconnect();
    }, [hasStickyToolbar]);

    const titleBlock = (
        <div className="flex w-full flex-col gap-2">
            <div className="flex w-full flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
                <div className="flex w-full flex-col gap-0">
                    <h1 className="min-w-0 flex-1 text-2xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    {subtitle && <div className="h-6 w-full">{subtitle}</div>}
                </div>
                {actions ?? null}
            </div>
        </div>
    );

    if (!withPaddingShell) {
        return titleBlock;
    }

    if (!hasStickyToolbar) {
        return (
            <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6 px-4 lg:px-6">
                {titleBlock}
            </div>
        );
    }

    return (
        <>
            <div
                ref={sentinelRef}
                className="pointer-events-none h-px w-full shrink-0"
                aria-hidden
            />
            <div
                className={cn(
                    'sticky top-0 z-90 transition-[box-shadow,backdrop-filter,background-color] duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] motion-reduce:transition-none',
                    isStuck
                        ? 'bg-background/75 shadow-sm backdrop-blur-md backdrop-saturate-150 supports-[backdrop-filter]:bg-background/65 dark:bg-background/55 dark:supports-[backdrop-filter]:bg-background/45 motion-reduce:bg-background motion-reduce:backdrop-blur-none'
                        : 'bg-background',
                )}
            >
                <div
                    className={cn(
                        'grid overflow-hidden transition-[grid-template-rows,border-color] duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] motion-reduce:duration-0',
                        isStuck
                            ? 'grid-rows-[1fr] border-b border-border'
                            : 'grid-rows-[0fr] border-b border-transparent',
                    )}
                    aria-hidden={!isStuck}
                >
                    <div className="min-h-0 overflow-hidden">
                        <div
                            className={cn(
                                'flex min-h-10 w-full items-center px-4 py-2 transition-opacity duration-500 ease-[cubic-bezier(0.33,1,0.68,1)] motion-reduce:duration-0 lg:px-6',
                                isStuck
                                    ? 'opacity-100'
                                    : 'pointer-events-none opacity-0',
                            )}
                        >
                            {stickyTitle}
                        </div>
                    </div>
                </div>
                <div className="flex flex-col gap-4 px-4 py-4 md:gap-6 md:py-6 lg:px-6">
                    {titleBlock}
                </div>
            </div>
        </>
    );
}
