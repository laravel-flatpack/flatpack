import { FlatpackBreadcrumbTrail } from '@/components/shell/flatpack-breadcrumb-trail';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { FlatpackBreadcrumb } from '@/types/flatpack';

type TopToolbarProps = {
    breadcrumbs?: FlatpackBreadcrumb[];
    className?: string;
};

/** Sidebar trigger plus optional breadcrumb trail for `SiteHeader` and `PageHeader` sticky rows. */
export function TopToolbar({ breadcrumbs, className }: TopToolbarProps) {
    const hasBreadcrumbs = Array.isArray(breadcrumbs) && breadcrumbs.length > 0;

    return (
        <div
            className={cn(
                'flex w-full min-h-10 shrink-0 items-center gap-2',
                className,
            )}
        >
            <SidebarTrigger className="-ml-1" />
            {hasBreadcrumbs ? (
                <FlatpackBreadcrumbTrail breadcrumbs={breadcrumbs} />
            ) : null}
        </div>
    );
}
