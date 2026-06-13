import { TopToolbar } from '@/components/shell/top-toolbar';
import type { FlatpackBreadcrumb } from '@/types/flatpack';

export function SiteHeader({
    breadcrumbs,
}: {
    breadcrumbs?: FlatpackBreadcrumb[];
}) {
    return (
        <header className="flex h-(--header-height) shrink-0 flex-col border-b transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-(--header-height)">
            <TopToolbar
                breadcrumbs={breadcrumbs}
                className="px-4 py-2 lg:px-6"
            />
        </header>
    );
}
