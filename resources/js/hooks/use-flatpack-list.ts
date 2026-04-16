import { router } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { firstErrorMessage } from '@/lib/form-errors';
import {
    listYamlColumnsToDataTableColumns,
    listYamlFiltersToDataTableFilters,
} from '@/lib/list-schema';
import { route } from '@/lib/route';
import type { DataTableBulkDeletePayload } from '@/types/data-table';
import type {
    FlatpackListHeaderAction,
    FlatpackListPageProps,
} from '@/types/pages/flatpack';

export function useFlatpackList({
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

    const noContentMessage = !displayName
        ? 'Nothing to list yet.'
        : columns.length === 0
          ? 'Define columns in list.yaml to render this table.'
          : null;

    return {
        displayName,
        pageTitle,
        noContentMessage,
        columns,
        filterDefinitions,
        reorderable,
        rowClickEditKey,
        pendingListConfirm,
        setPendingListConfirm,
        handleRowClick,
        handleServerPaginationChange,
        handleBulkAction,
        handleRowAction,
        executeListAction,
        handleCellUpdate,
        handleRowUpdate,
        records,
        pagination,
        searchTerm,
        serverFilterValues,
        serverSorting,
        bulkActions,
        listActions,
        entity,
        modelKey,
    };
}
