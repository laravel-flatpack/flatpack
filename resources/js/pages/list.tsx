import { Head, router } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';
import { DataTable } from '@/components/table/data-table';
import FlatpackLayout from '@/layouts/flatpack-layout';
import { listYamlColumnsToDataTableColumns } from '@/lib/list-schema';
import type { FlatpackListPageProps } from '@/types/pages/flatpack';

export default function FlatpackListPage({
    entity,
    name,
    schema,
    records = [],
    pagination,
}: FlatpackListPageProps) {
    const displayName = name ?? entity ?? '';
    const pageTitle = displayName ? `${displayName} list` : '';

    const columns = useMemo(
        () => listYamlColumnsToDataTableColumns(schema?.columns),
        [schema],
    );

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
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {displayName}
                    </h1>
                ) : null}
                {!displayName ? (
                    <p className="text-muted-foreground">
                        Nothing to list yet.
                    </p>
                ) : columns.length === 0 ? (
                    <p className="text-muted-foreground">
                        Define columns in list.yaml to render this table.
                    </p>
                ) : (
                    <DataTable
                        id={`flatpack-list-${entity || 'entity'}`}
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
