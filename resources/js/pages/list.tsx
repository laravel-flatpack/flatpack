import { Head, Link, router } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { LucideIconByName } from '@/components/icons';
import { DataTable } from '@/components/table/data-table';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import FlatpackLayout from '@/layouts/flatpack-layout';
import {
    listYamlColumnsToDataTableColumns,
    listYamlFiltersToDataTableFilters,
} from '@/lib/list-schema';
import { route } from '@/lib/route';
import { cn } from '@/lib/utils';
import type { DataTableBulkDeletePayload } from '@/types/data-table';
import type {
    FlatpackListHeaderAction,
    FlatpackListPageProps,
} from '@/types/pages/flatpack';

function firstErrorMessage(
    errors: Record<string, unknown>,
): string | undefined {
    for (const value of Object.values(errors)) {
        if (typeof value === 'string' && value.trim() !== '') {
            return value;
        }
        if (Array.isArray(value)) {
            const first = value.find(
                (item): item is string =>
                    typeof item === 'string' && item.trim() !== '',
            );
            if (first) {
                return first;
            }
        }
    }

    return undefined;
}

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
    sorting: serverSorting = { sort_by: null, sort_direction: null },
    list_actions: listActions = [],
    bulk_actions: bulkActions = [],
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

    const [pendingListConfirm, setPendingListConfirm] = useState<
        (FlatpackListHeaderAction & { action: string }) | null
    >(null);

    const handleRowClick = useCallback(
        (row: Record<string, unknown>) => {
            if (rowClickEditKey === null) {
                return;
            }
            const record = row[rowClickEditKey];
            if (record == null || record === '') {
                return;
            }
            router.get(
                route('flatpack.entities.edit', {
                    entity,
                    record: String(record),
                }),
            );
        },
        [entity, rowClickEditKey],
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
            sorting?: {
                sort_by: string | null;
                sort_direction: 'asc' | 'desc' | null;
            },
        ) => {
            router.get(
                route('flatpack.entities.index', { entity }),
                {
                    page,
                    per_page: perPage,
                    search,
                    filters,
                    sort_by: sorting?.sort_by ?? null,
                    sort_direction: sorting?.sort_direction ?? null,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        },
        [entity],
    );
    const handleBulkAction = useCallback(
        async (payload: DataTableBulkDeletePayload) => {
            await new Promise<void>((resolve, reject) => {
                router.post(
                    route('flatpack.entities.bulk-action', { entity }),
                    {
                        action: payload.action,
                        selection: payload.selection,
                        search: payload.search,
                        filters: payload.filters,
                        sort_by: payload.sorting.sort_by,
                        sort_direction: payload.sorting.sort_direction,
                    },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            resolve();
                            const cfg = bulkActions.find(
                                (a) => a.action === payload.action,
                            );
                            if (cfg?.success_message) {
                                toast.success(cfg.success_message);
                            }
                        },
                        onError: (errors) =>
                            reject(
                                new Error(
                                    firstErrorMessage(errors) ??
                                        'Bulk action failed',
                                ),
                            ),
                    },
                );
            });
        },
        [bulkActions, entity],
    );
    const handleRowAction = useCallback(
        async ({
            action,
            row,
        }: {
            action: string;
            row: Record<string, unknown>;
        }) => {
            const record = row[modelKey || 'id'];
            if (record == null || record === '') {
                throw new Error('Record key is missing');
            }
            await new Promise<void>((resolve, reject) => {
                router.post(
                    route('flatpack.entities.row-action', {
                        entity,
                        record: String(record),
                    }),
                    { action },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => resolve(),
                        onError: (errors) => {
                            const message =
                                firstErrorMessage(errors) ??
                                'Row action failed';
                            toast.error(message);
                            reject(new Error(message));
                        },
                    },
                );
            });
        },
        [entity, modelKey],
    );
    const executeListAction = useCallback(
        async (config: FlatpackListHeaderAction & { action: string }) => {
            const { action } = config;
            await new Promise<void>((resolve, reject) => {
                router.post(
                    route('flatpack.entities.action', { entity }),
                    { action },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            resolve();
                            if (config.success_message) {
                                toast.success(config.success_message);
                            }
                        },
                        onError: (errors) => {
                            const message =
                                firstErrorMessage(errors) ??
                                'List action failed';
                            toast.error(message);
                            reject(new Error(message));
                        },
                    },
                );
            });
        },
        [entity],
    );
    const handleCellUpdate = useCallback(
        async ({
            row,
            columnId,
            value,
        }: {
            row: Record<string, unknown>;
            columnId: string;
            value: unknown;
        }) => {
            const record = row[modelKey || 'id'];
            if (record == null || record === '') {
                throw new Error('Record key is missing');
            }
            await new Promise<void>((resolve, reject) => {
                router.patch(
                    route('flatpack.entities.update', {
                        entity,
                        record: String(record),
                    }),
                    {
                        field: columnId,
                        value: value as never,
                    },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => resolve(),
                        onError: (errors) => {
                            const message =
                                firstErrorMessage(errors) ??
                                'Record update failed';
                            toast.error(message);
                            reject(new Error(message));
                        },
                    },
                );
            });
        },
        [entity, modelKey],
    );
    const handleRowUpdate = useCallback(
        async ({ row }: { row: Record<string, unknown> }) => {
            const record = row[modelKey || 'id'];
            if (record == null || record === '') {
                throw new Error('Record key is missing');
            }
            await new Promise<void>((resolve, reject) => {
                router.patch(
                    route('flatpack.entities.update', {
                        entity,
                        record: String(record),
                    }),
                    { values: row as never },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => resolve(),
                        onError: (errors) => {
                            const message =
                                firstErrorMessage(errors) ??
                                'Record update failed';
                            toast.error(message);
                            reject(new Error(message));
                        },
                    },
                );
            });
        },
        [entity, modelKey],
    );

    return (
        <>
            {displayName ? <Head title={pageTitle} /> : null}
            <AlertDialog
                open={pendingListConfirm !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingListConfirm(null);
                    }
                }}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {pendingListConfirm?.label ?? 'Confirm'}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to continue?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => {
                                const pending = pendingListConfirm;
                                setPendingListConfirm(null);
                                if (pending !== null) {
                                    void executeListAction(pending);
                                }
                            }}
                        >
                            Continue
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
            <div className="flex flex-col gap-2">
                {displayName ? (
                    <div className="mb-4 flex h-10 w-full flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
                        <h1 className="min-w-0 flex-1 text-2xl font-semibold tracking-tight">
                            {displayName}
                        </h1>
                        <div className="flex shrink-0 flex-wrap items-center justify-start gap-2 sm:justify-end">
                            {listActions.map((action) =>
                                'href' in action ? (
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
                                ) : (
                                    <Button
                                        key={action.id}
                                        type="button"
                                        size="lg"
                                        variant={action.variant ?? 'outline'}
                                        className={cn(
                                            action.icon &&
                                                'inline-flex items-center gap-1.5',
                                        )}
                                        onClick={() => {
                                            if (action.confirm) {
                                                setPendingListConfirm(action);
                                                return;
                                            }
                                            void executeListAction(action);
                                        }}
                                    >
                                        {action.icon ? (
                                            <LucideIconByName
                                                name={action.icon}
                                            />
                                        ) : null}
                                        {action.label}
                                    </Button>
                                ),
                            )}
                        </div>
                    </div>
                ) : null}
                {noContentMessage ? (
                    <p className="text-muted-foreground">{noContentMessage}</p>
                ) : (
                    <DataTable
                        id={`flatpack-list-${entity || 'entity'}`}
                        dataRowKey={modelKey || 'id'}
                        bulkActions={bulkActions}
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
                        serverSorting={serverSorting}
                        onBulkAction={handleBulkAction}
                        onRowAction={handleRowAction}
                        onCellUpdate={handleCellUpdate}
                        onRowUpdate={handleRowUpdate}
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
