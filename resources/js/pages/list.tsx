import { Head, Link, router } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';
import { LucideIconByName } from '@/components/icons';
import { DataTable } from '@/components/table/data-table';
import { Button } from '@/components/ui/button';
import FlatpackLayout from '@/layouts/flatpack-layout';
import {
    listYamlColumnsToDataTableColumns,
    listYamlFiltersToDataTableFilters,
} from '@/lib/list-schema';
import { cn } from '@/lib/utils';
import type { FlatpackListPageProps } from '@/types/pages/flatpack';

export default function FlatpackListPage({
    entity,
    name,
    model_key: modelKey,
    schema,
    records = [],
    pagination,
    search_term: searchTerm = '',
    filters: serverFilters = [],
    filter_values: serverFilterValues = {},
    flatpack_prefix: flatpackPrefix,
    list_actions: listActions = [],
}: FlatpackListPageProps) {
    const displayName = name ?? entity ?? '';
    const pageTitle = displayName ? `${displayName} list` : '';

    const columns = useMemo(
        () => listYamlColumnsToDataTableColumns(schema?.columns),
        [schema],
    );
    const filterDefinitions = useMemo(
        () =>
            serverFilters.length > 0
                ? serverFilters
                : listYamlFiltersToDataTableFilters(columns, schema?.filters),
        [columns, schema?.filters, serverFilters],
    );

    const checkboxes = schema?.checkboxes === true;
    const reorderable =
        typeof schema?.reorderable === 'string'
            ? schema.reorderable
            : schema?.reorderable === true;
    const rowClickEditKey =
        typeof schema?.row_click_edit === 'string'
            ? schema.row_click_edit
            : schema?.row_click_edit === false
              ? null
              : modelKey || 'id';
    const normalizedPrefix = (flatpackPrefix ?? 'flatpack').replace(
        /^\/+|\/+$/g,
        '',
    );

    const handleRowClick = useCallback(
        (row: Record<string, unknown>) => {
            if (rowClickEditKey === null) {
                return;
            }
            const record = row[rowClickEditKey];
            if (record == null || record === '') {
                return;
            }
            const recordValue = encodeURIComponent(String(record));
            router.get(`/${normalizedPrefix}/${entity}/${recordValue}/edit`);
        },
        [entity, normalizedPrefix, rowClickEditKey],
    );
    const noContentMessage = !displayName
        ? 'Nothing to list yet.'
        : columns.length === 0
          ? 'Define columns in list.yaml to render this table.'
          : null;

    const handleServerPaginationChange = useCallback(
        (
            page: number,
            perPage: number,
            search?: string,
            filters?: Record<string, string | string[] | null>,
        ) => {
            router.get(
                window.location.pathname,
                { page, per_page: perPage, search, filters },
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
                        onRowClick={
                            rowClickEditKey !== null
                                ? handleRowClick
                                : undefined
                        }
                        columns={columns}
                        data={records}
                        serverPagination={pagination}
                        serverSearch={searchTerm}
                        serverFilters={filterDefinitions}
                        serverFilterValues={serverFilterValues}
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
