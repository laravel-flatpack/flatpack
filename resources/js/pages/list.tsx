import { Head, Link, router } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';
import { LucideIconByName } from '@/components/icons';
import { DataTable } from '@/components/table/data-table';
import { Button } from '@/components/ui/button';
import FlatpackLayout from '@/layouts/flatpack-layout';
import { listYamlColumnsToDataTableColumns } from '@/lib/list-schema';
import { cn } from '@/lib/utils';
import type { FlatpackListPageProps } from '@/types/pages/flatpack';

export default function FlatpackListPage({
    entity,
    name,
    schema,
    records = [],
    pagination,
    list_actions: listActions = [],
}: FlatpackListPageProps) {
    const displayName = name ?? entity ?? '';
    const pageTitle = displayName ? `${displayName} list` : '';

    const columns = useMemo(
        () => listYamlColumnsToDataTableColumns(schema?.columns),
        [schema],
    );

    const checkboxes = schema?.checkboxes === true;
    const reorderable =
        typeof schema?.reorderable === 'string'
            ? schema.reorderable
            : schema?.reorderable === true;
    const noContentMessage = !displayName
        ? 'Nothing to list yet.'
        : columns.length === 0
          ? 'Define columns in list.yaml to render this table.'
          : null;

    const handleServerPaginationChange = useCallback(
        (page: number, perPage: number) => {
            router.get(
                window.location.pathname,
                { page, per_page: perPage },
                {
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        },
        [],
    );

    return (
        <>
            {displayName ? <Head title={pageTitle} /> : null}
            <div className="flex flex-col gap-2">
                {displayName ? (
                    <div className="mb-4 flex h-10 w-full flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
                        <h1 className="min-w-0 flex-1 text-2xl font-semibold tracking-tight">
                            {displayName}
                        </h1>
                        <div className="flex shrink-0 flex-wrap items-center justify-start gap-2 sm:justify-end">
                            {listActions.map((action) => (
                                <Button
                                    key={action.id}
                                    asChild
                                    size="lg"
                                    variant={action.variant ?? 'outline'}
                                >
                                    <Link
                                        href={action.href}
                                        className={cn(
                                            action.icon &&
                                                'inline-flex items-center gap-1.5',
                                        )}
                                    >
                                        {action.icon ? (
                                            <LucideIconByName
                                                name={action.icon}
                                            />
                                        ) : null}
                                        {action.label}
                                    </Link>
                                </Button>
                            ))}
                        </div>
                    </div>
                ) : null}
                {noContentMessage ? (
                    <p className="text-muted-foreground">{noContentMessage}</p>
                ) : (
                    <DataTable
                        id={`flatpack-list-${entity || 'entity'}`}
                        checkboxes={checkboxes}
                        reorderable={reorderable}
                        columns={columns}
                        data={records}
                        serverPagination={pagination}
                        onServerPaginationChange={
                            pagination
                                ? handleServerPaginationChange
                                : undefined
                        }
                    />
                )}
            </div>
        </>
    );
}

FlatpackListPage.layout = (page: React.ReactElement) => (
    <FlatpackLayout>{page}</FlatpackLayout>
);
