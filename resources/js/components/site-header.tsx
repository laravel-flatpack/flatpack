import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { FlatpackBreadcrumb } from '@/types/flatpack';

export function SiteHeader({
    title,
    breadcrumbs,
}: {
    title?: string;
    breadcrumbs?: FlatpackBreadcrumb[];
}) {
    const hasBreadcrumbs = Array.isArray(breadcrumbs) && breadcrumbs.length > 0;

    return (
        <header className="flex h-(--header-height) shrink-0 flex-col border-b transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-(--header-height)">
            <div className="w-full min-h-10 flex items-center gap-2 px-4 py-2 lg:gap-2 lg:px-6">
                <SidebarTrigger className="-ml-1" />
                {hasBreadcrumbs && (
                    <Breadcrumb>
                        <BreadcrumbList>
                            {breadcrumbs.map((crumb, i) => {
                                const isLast = i === breadcrumbs.length - 1;
                                return (
                                    <Fragment
                                        key={`${crumb.label}-${crumb.href ?? ''}`}
                                    >
                                        <BreadcrumbItem className="max-w-[min(100%,12rem)] sm:max-w-none">
                                            {crumb.href !== null &&
                                            crumb.href !== '' &&
                                            !isLast ? (
                                                <BreadcrumbLink asChild>
                                                    <Link href={crumb.href}>
                                                        {crumb.label}
                                                    </Link>
                                                </BreadcrumbLink>
                                            ) : (
                                                <BreadcrumbPage className="truncate">
                                                    {crumb.label}
                                                </BreadcrumbPage>
                                            )}
                                        </BreadcrumbItem>
                                        {!isLast && (
                                            <BreadcrumbSeparator aria-hidden />
                                        )}
                                    </Fragment>
                                );
                            })}
                        </BreadcrumbList>
                    </Breadcrumb>
                )}
            </div>
            {/*<div className="flex px-4 lg:px-6">
                SearchBar
            </div>*/}
        </header>
    );
}
