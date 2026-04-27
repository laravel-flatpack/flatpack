import { router } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { firstErrorMessage } from '@/lib/form-errors';
import {
    listYamlColumnsToDataTableColumns,
    listYamlFiltersToDataTableFilters,
} from '@/lib/list-schema';
import { route } from '@/lib/route';
import type {
    DataTableBulkDeletePayload,
    DataTableRowActionPayload,
    FlatpackActionVariant,
} from '@/types/data-table';
import type {
    FlatpackListCompositionColumnsYaml,
    FlatpackListTabPanelLayout,
} from '@/types/list-composition';
import type {
    FlatpackListHeaderAction,
    FlatpackListPageProps,
} from '@/types/pages/flatpack';

function isListTabPanel(value: unknown): value is FlatpackListTabPanelLayout {
    if (value === null || typeof value !== 'object') {
        return false;
    }
    const o = value as Record<string, unknown>;
    if (typeof o.id !== 'string' || o.id.trim() === '') {
        return false;
    }
    if (typeof o.label !== 'string' || o.label.trim() === '') {
        return false;
    }

    return Array.isArray(o.column_ids);
}

function isColumnsYaml(
    value: unknown,
): value is FlatpackListCompositionColumnsYaml {
    return (
        Array.isArray(value) || (value !== null && typeof value === 'object')
    );
}

export function useFlatpackList({
    entity,
    name,
    model_key: modelKey,
    schema,
    records = [],
    pagination,
    search_term: searchTerm = '',
    active_tab: serverActiveTab = null,
    filters: serverFilters = [],
    filter_values: serverFilterValues = {},
    sorting: serverSorting = { sort_by: null, sort_direction: null },
    list_actions: listActions = [],
    bulk_actions: bulkActions = [],
}: FlatpackListPageProps) {
    const displayName = name ?? entity ?? '';
    const pageTitle = displayName ? `${displayName} list` : '';

    const allColumns = useMemo(
        () => listYamlColumnsToDataTableColumns(schema?.columns),
        [schema],
    );

    const listTabPanels = useMemo((): FlatpackListTabPanelLayout[] => {
        const raw = schema?.tab_panels;
        if (!Array.isArray(raw)) {
            return [];
        }
        return raw.filter(isListTabPanel);
    }, [schema]);

    const [listActiveTabId, setListActiveTabId] = useState<string | null>(
        serverActiveTab,
    );

    useEffect(() => {
        if (listTabPanels.length === 0) {
            if (listActiveTabId !== null) {
                setListActiveTabId(null);
            }
            return;
        }
        if (
            serverActiveTab !== null &&
            listTabPanels.some((p) => p.id === serverActiveTab) &&
            serverActiveTab !== listActiveTabId
        ) {
            setListActiveTabId(serverActiveTab);
            return;
        }
        if (listTabPanels.length > 0) {
            if (
                listActiveTabId === null ||
                !listTabPanels.some((p) => p.id === listActiveTabId)
            ) {
                setListActiveTabId(listTabPanels[0].id);
            }
        }
    }, [listActiveTabId, listTabPanels, serverActiveTab]);

    const columns = useMemo(() => {
        if (listTabPanels.length === 0) {
            return allColumns;
        }
        const activeId = listActiveTabId ?? listTabPanels[0]?.id ?? '';
        const panel = listTabPanels.find((p) => p.id === activeId);
        if (panel === undefined) {
            return allColumns;
        }
        if (
            isColumnsYaml(panel.columns) &&
            (Array.isArray(panel.columns)
                ? panel.columns.length > 0
                : Object.keys(panel.columns).length > 0)
        ) {
            return listYamlColumnsToDataTableColumns(panel.columns);
        }
        const allowed = new Set(panel.column_ids);
        return allColumns.filter((c) => allowed.has(c.id));
    }, [allColumns, listActiveTabId, listTabPanels]);

    const handleListTabChange = useCallback(
        (tabId: string) => {
            if (tabId === '') {
                return;
            }
            setListActiveTabId(tabId);
            router.get(
                route('flatpack.entities.index', { entity }),
                {
                    page: 1,
                    per_page: pagination?.per_page ?? 10,
                    search: searchTerm,
                    filters: serverFilterValues,
                    sort_by: serverSorting.sort_by ?? null,
                    sort_direction: serverSorting.sort_direction ?? null,
                    tab: tabId,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onError: (errors) => {
                        toast.error(
                            firstErrorMessage(errors) ?? 'Invalid tab scope',
                        );
                        setListActiveTabId(
                            serverActiveTab ?? listTabPanels[0]?.id ?? null,
                        );
                    },
                },
            );
        },
        [
            entity,
            listTabPanels,
            pagination?.per_page,
            searchTerm,
            serverActiveTab,
            serverFilterValues,
            serverSorting,
        ],
    );

    const filterDefinitions = useMemo(
        () =>
            serverFilters.length > 0
                ? serverFilters
                : listYamlFiltersToDataTableFilters(
                      allColumns,
                      schema?.filters,
                  ),
        [allColumns, schema?.filters, serverFilters],
    );

    const reorderable =
        typeof schema?.reorderable === 'string'
            ? schema.reorderable
            : schema?.reorderable === true;
    const rowClickBehavior = schema?.row_click ?? 'none';
    const isRowClickEditPage = rowClickBehavior === 'edit_page';
    const rowClickRecordKey = modelKey || 'id';

    const [pendingListConfirm, setPendingListConfirm] = useState<
        (FlatpackListHeaderAction & { action: string }) | null
    >(null);
    const [pendingRowActionConfirm, setPendingRowActionConfirm] = useState<{
        action: string;
        row: Record<string, unknown>;
        label: string;
        variant?: FlatpackActionVariant;
        success_message?: string;
    } | null>(null);

    const handleRowClick = useCallback(
        (row: Record<string, unknown>) => {
            if (!isRowClickEditPage) {
                return;
            }
            const record = row[rowClickRecordKey];
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
        [entity, isRowClickEditPage, rowClickRecordKey],
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
                    tab: listActiveTabId,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        },
        [entity, listActiveTabId],
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

    const executeRowAction = useCallback(
        async (opts: {
            action: string;
            row: Record<string, unknown>;
            success_message?: string;
        }) => {
            const record = opts.row[modelKey || 'id'];
            if (record == null || record === '') {
                throw new Error('Record key is missing');
            }
            await new Promise<void>((resolve, reject) => {
                router.post(
                    route('flatpack.entities.row-action', {
                        entity,
                        record: String(record),
                    }),
                    { action: opts.action },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            resolve();
                            if (opts.success_message) {
                                toast.success(opts.success_message);
                            }
                        },
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

    const handleRowAction = useCallback(
        async (payload: DataTableRowActionPayload) => {
            const { action, row, button } = payload;
            if (button?.confirm === true) {
                setPendingRowActionConfirm({
                    action,
                    row,
                    label: button.label,
                    variant: button.variant,
                    success_message: button.success_message,
                });
                return;
            }
            await executeRowAction({
                action,
                row,
                success_message: button?.success_message,
            });
        },
        [executeRowAction],
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
        : allColumns.length === 0
          ? 'Define columns in list.yaml to render this table.'
          : null;

    return {
        displayName,
        pageTitle,
        noContentMessage,
        columns,
        allColumns,
        listTabPanels,
        listActiveTabId,
        handleListTabChange,
        filterDefinitions,
        reorderable,
        isRowClickEditPage,
        pendingListConfirm,
        pendingRowActionConfirm,
        setPendingListConfirm,
        setPendingRowActionConfirm,
        executeRowAction,
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
