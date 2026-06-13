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
import type { FlatpackBreadcrumb } from '@/types/flatpack';

export function FlatpackBreadcrumbTrail({
    breadcrumbs,
}: {
    breadcrumbs: FlatpackBreadcrumb[];
}) {
    return (
        <Breadcrumb>
            <BreadcrumbList>
                {breadcrumbs.map((crumb, i) => {
                    const isLast = i === breadcrumbs.length - 1;
                    return (
                        <Fragment key={`${crumb.label}-${crumb.href ?? ''}`}>
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
                            {!isLast && <BreadcrumbSeparator aria-hidden />}
                        </Fragment>
                    );
                })}
            </BreadcrumbList>
        </Breadcrumb>
    );
}
