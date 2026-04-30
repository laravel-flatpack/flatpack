/**
 * Data table orchestration: TanStack state, sub-hooks, and row/drawer invariants.
 *
 * Sub-hook responsibilities:
 * - {@link useDataTableCreateRowFlow} — toolbar `create` / `add` / `attach` draft path, `onToolbarAction` delegation, drawer open/close
 * - {@link useDataTableCellUpdateFlow} — inline cell edits, rollback
 * - {@link useDataTableRowReplaceFlow} — drawer save / new row commit, `deferNotifyParentFormValues` for `onValueChange`
 * - {@link useDataTableRelationshipFlow} — row/bulk relationship actions, destructive confirm
 * - {@link useSortable} — drag order + optimistic persistence
 * - {@link useDataTableServerState} — pagination, filters, sorting, search when `serverPagination` is set
 *
 * Invariants (embedded form / list tables):
 * - Draft toolbar open does not call `onValueChange`; parent sees updates on save/row replace (`deferNotifyParentFormValues` in cell/row-replace paths).
 * - Create/add/attach `action` keys: see `data-table-action-semantics` and `useDataTableCreateRowFlow`.
 * - Do not key toolbar behavior by button `id`, only by each button’s `action` string in `useDataTableCreateRowFlow`.
 */
import type { DragEndEvent } from '@dnd-kit/core';
import {
    type Column,
    type ColumnFiltersState,
    functionalUpdate,
    getCoreRowModel,
    getFacetedRowModel,
    getFacetedUniqueValues,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    type RowSelectionState,
    type Table,
    useReactTable,
    type VisibilityState,
} from '@tanstack/react-table';
import * as React from 'react';
import { toast } from 'sonner';
import { DataTableBodyWithFooter } from '@/components/table/data-table-body-with-footer';
import { buildDataTableColumnDefs } from '@/components/table/data-table-column-defs';
import {
    leafColumnIdsInSchemaOrder,
    visibilityFromSchema,
} from '@/components/table/data-table-column-visibility';
import { DATA_TABLE_ROW_CLICK_IGNORE_SELECTOR } from '@/components/table/data-table-constants';
import { useDataTableCellUpdateFlow } from '@/hooks/use-data-table-cell-update-flow';
import { useDataTableCreateRowFlow } from '@/hooks/use-data-table-create-row-flow';
import { useDataTableRelationshipFlow } from '@/hooks/use-data-table-relationship-flow';
import { useDataTableRowReplaceFlow } from '@/hooks/use-data-table-row-replace-flow';
import { useDataTableServerState } from '@/hooks/use-data-table-server-state';
import { useSortable } from '@/hooks/use-sortable';
import { isEmbeddedTableEditRowAction } from '@/lib/data-table-action-semantics';
import { stableRowId } from '@/lib/data-table-utils';
import { DEFAULT_LIST_ROW_REORDER_COLUMN } from '@/lib/generated/composition-schema-keys';
import type {
    DataTableProps,
    DataTableRowActionPayload,
    DataTableRowDrawerBodyVariant,
    DataTableRowValidationFieldErrorsById,
    DataTableRowValidationMessagesById,
    FlatpackDataTableBulkAction,
    FlatpackDataTableColumn,
    FlatpackDataTableFilter,
    FlatpackFormTableToolbarAction,
} from '@/types/data-table';

/**
 * Return value of {@link useDataTableController} for `DataTable` (toolbar, `tableAndFooter`, row drawer, confirm).
 */
export type DataTableController = {
    id: string;
    table: Table<Record<string, unknown>>;
    hasToolbarActions: boolean;
    toolbarActions: FlatpackFormTableToolbarAction[];
    handleToolbarActionClick: (actionId: string) => void;
    toolbarActionsDisabled: boolean;
    toolbarActionsDisabledTitle: string | undefined;
    hasBulkActions: boolean;
    selectedRowCount: number;
    isAllRowsSelected: boolean;
    totalRowCount: number;
    handleSelectAllRows: () => void;
    handleDeselectAllRows: () => void;
    bulkActions: FlatpackDataTableBulkAction[];
    handleBulkActionClick: (actionId: string) => void;
    hasSearchableColumns: boolean;
    hasFilters: boolean;
    globalFilter: string;
    setGlobalFilter: (v: string) => void;
    serverFilters: FlatpackDataTableFilter[];
    serverFilterState: ReturnType<
        typeof useDataTableServerState
    >['serverFilterState'];
    setSingleServerFilter: ReturnType<
        typeof useDataTableServerState
    >['setSingleServerFilter'];
    toggleMultiServerFilterValue: ReturnType<
        typeof useDataTableServerState
    >['toggleMultiServerFilterValue'];
    setDateServerFilter: ReturnType<
        typeof useDataTableServerState
    >['setDateServerFilter'];
    tableAndFooter: React.ReactNode;
    rowDetailDrawer: boolean;
    detailDrawerOpen: boolean;
    detailDrawerRow: Record<string, unknown> | null;
    detailDrawerRowId: string | null;
    detailDrawerTitleColumn: FlatpackDataTableColumn | null;
    schemaColumns: DataTableProps['columns'];
    handleDetailDrawerOpenChange: (open: boolean) => void;
    handleRowReplace: (rowId: string, next: Record<string, unknown>) => void;
    pendingEmbeddedRowConfirm: ReturnType<
        typeof useDataTableRelationshipFlow
    >['pendingEmbeddedRowConfirm'];
    dismissPendingRowActionConfirm: () => void;
    confirmPendingRowAction: () => void;
    tableLabelId: string;
    /** BelongsToMany `attach` toolbar: `attachExisting` vs default column field list. */
    detailDrawerBodyVariant: DataTableRowDrawerBodyVariant;
    /** Optional BTM “pick existing” slot when `detailDrawerBodyVariant` is `attachExisting`. */
    renderRowDrawerAttachBody: DataTableProps['renderRowDrawerAttachBody'];
    rowValidationMessagesById: DataTableRowValidationMessagesById;
    rowValidationFieldErrorsById: DataTableRowValidationFieldErrorsById;
};

export function useDataTableController(
    props: DataTableProps,
): DataTableController {
    const {
        id,
        columns: schemaColumns,
        data: initialData,
        dataRowKey = 'id',
        bulkActions = [],
        toolbarActions = [],
        toolbarActionsDisabled = false,
        toolbarActionsDisabledTitle,
        onToolbarAction,
        rowDetailDrawer = false,
        openDetailDrawerOnRowClick: openDetailDrawerOnRowClickProp = true,
        reorderable: reorderableProp,
        onRowClick,
        onValueChange,
        onBulkAction,
        onRowAction,
        requireRowIdForActions = false,
        onCellUpdate,
        onRowUpdate,
        reorderEndpoint,
        reorderOnError,
        serverPagination,
        pagination: paginationVisibility,
        serverSearch,
        serverFilters = [],
        serverFilterValues = {},
        serverSorting = { sort_by: null, sort_direction: null },
        defaultSort,
        onServerPaginationChange,
        renderRowDrawerAttachBody,
        rowValidationMessagesById = {},
        rowValidationFieldErrorsById = {},
    } = props;

    const openDetailDrawerOnRowClick =
        rowDetailDrawer && openDetailDrawerOnRowClickProp;
    const hasToolbarActions = toolbarActions.length > 0;

    const [data, setData] = React.useState<Record<string, unknown>[]>(
        () => initialData,
    );
    React.useLayoutEffect(() => {
        setData(initialData);
    }, [initialData]);
    const [rowSelection, setRowSelection] = React.useState<RowSelectionState>(
        {},
    );
    const getStableRowId = React.useCallback(
        (row: Record<string, unknown>, index: number): string => {
            const keyValue = row[dataRowKey];
            if (keyValue !== undefined && keyValue !== null) {
                return String(keyValue);
            }
            return stableRowId(row, index);
        },
        [dataRowKey],
    );
    const {
        newRowIdPrefix,
        detailDrawerOpen,
        detailDrawerRowId,
        detailDrawerRow,
        detailDrawerBodyVariant,
        openDetailDrawerForRow,
        handleDetailDrawerOpenChange,
        clearCreateDraftRow,
        handleToolbarActionClick,
    } = useDataTableCreateRowFlow({
        rowDetailDrawer,
        toolbarActions,
        schemaColumns,
        rowIdentity: {
            getStableRowId,
        },
        mutations: {
            data,
            onToolbarAction,
        },
    });
    const [isAllRowsSelected, setIsAllRowsSelected] = React.useState(false);
    React.useEffect(() => {
        if (!isAllRowsSelected) {
            return;
        }
        setRowSelection((prev) => {
            const next = { ...prev };
            for (const [index, row] of data.entries()) {
                next[getStableRowId(row, index)] = true;
            }
            return next;
        });
    }, [data, getStableRowId, isAllRowsSelected]);
    const hasBulkActions = bulkActions.length > 0;
    const reorderKey =
        reorderableProp === true
            ? DEFAULT_LIST_ROW_REORDER_COLUMN
            : typeof reorderableProp === 'string'
              ? reorderableProp
              : null;
    const isReorderable = reorderKey !== null;
    const hasSearchableColumns = React.useMemo(
        () => schemaColumns.some((col) => col.searchable === true),
        [schemaColumns],
    );
    const hasFilters = React.useMemo(
        () => serverFilters.length > 0,
        [serverFilters],
    );
    const allowedSortingColumnIds = React.useMemo(
        () => schemaColumns.map((column) => column.id),
        [schemaColumns],
    );
    const [columnVisibility, setColumnVisibility] =
        React.useState<VisibilityState>(() =>
            visibilityFromSchema(schemaColumns),
        );
    const schemaLeafOrder = React.useMemo(
        () =>
            leafColumnIdsInSchemaOrder(
                schemaColumns,
                hasBulkActions,
                isReorderable,
            ),
        [schemaColumns, hasBulkActions, isReorderable],
    );
    const [columnOrder, setColumnOrder] = React.useState<string[]>(() =>
        leafColumnIdsInSchemaOrder(
            schemaColumns,
            hasBulkActions,
            isReorderable,
        ),
    );
    React.useLayoutEffect(() => {
        setColumnOrder(schemaLeafOrder);
    }, [schemaLeafOrder]);
    const [columnFilters, setColumnFilters] =
        React.useState<ColumnFiltersState>([]);
    const {
        globalFilter,
        setGlobalFilter,
        serverFilterState,
        sorting,
        paginationState,
        handlePaginationChange,
        handleSortingChange,
        setSingleServerFilter,
        toggleMultiServerFilterValue,
        setDateServerFilter,
    } = useDataTableServerState({
        serverPagination,
        serverSearch,
        serverFilterValues,
        serverSorting,
        defaultSort,
        allowedSortingColumnIds,
        onServerPaginationChange,
    });

    const { handleCellChange } = useDataTableCellUpdateFlow({
        rowIdentity: {
            getStableRowId,
        },
        mutations: {
            setData,
            onValueChange,
            onCellUpdate,
        },
    });

    const { handleRowReplace } = useDataTableRowReplaceFlow({
        rowIdentity: {
            getStableRowId,
            newRowIdPrefix,
        },
        mutations: {
            setData,
            onValueChange,
            onRowUpdate,
        },
        clearCreateDraftRow,
    });

    const detailDrawerTitleColumn = React.useMemo(() => {
        const cols = schemaColumns.filter((c) => c.type !== 'actions');
        return cols[0] ?? schemaColumns[0] ?? null;
    }, [schemaColumns]);
    const resolveRowIdFromReference = React.useCallback(
        (row: Record<string, unknown>) => {
            const idx = data.indexOf(row);
            return idx >= 0 ? getStableRowId(row, idx) : getStableRowId(row, 0);
        },
        [data, getStableRowId],
    );

    const handleRowClick = React.useCallback(
        (
            event: React.MouseEvent<HTMLTableRowElement>,
            row: Record<string, unknown>,
        ) => {
            const target = event.target;
            if (
                target instanceof Element &&
                target.closest(DATA_TABLE_ROW_CLICK_IGNORE_SELECTOR)
            ) {
                return;
            }
            if (rowDetailDrawer && openDetailDrawerOnRowClick) {
                openDetailDrawerForRow(resolveRowIdFromReference(row));
                return;
            }
            if (onRowClick == null) {
                return;
            }
            if (!(target instanceof Element)) {
                onRowClick(row);
                return;
            }
            onRowClick(row);
        },
        [
            rowDetailDrawer,
            openDetailDrawerOnRowClick,
            resolveRowIdFromReference,
            openDetailDrawerForRow,
            onRowClick,
        ],
    );
    const serverSortingForBulkAction = React.useMemo(() => {
        const first = sorting[0];
        if (!first) {
            return { sort_by: null, sort_direction: null } as const;
        }
        return {
            sort_by: first.id,
            sort_direction: first.desc ? 'desc' : 'asc',
        } as const;
    }, [sorting]);
    const {
        handleRowAction: handleRelationshipRowAction,
        handleBulkAction: handleRelationshipBulkAction,
        pendingEmbeddedRowConfirm,
        dismissPendingRowActionConfirm,
        confirmPendingRowAction,
    } = useDataTableRelationshipFlow({
        rowIdentity: {
            dataRowKey,
            getStableRowId,
        },
        mutations: {
            data,
            setData,
            onValueChange,
        },
        onRowAction,
        onBulkAction,
        bulkActions,
        rowSelection,
        setRowSelection,
        isAllRowsSelected,
        setIsAllRowsSelected,
        globalFilter,
        serverFilterState,
        serverSortingForBulkAction,
    });

    const handleRowAction = React.useCallback(
        (payload: DataTableRowActionPayload) => {
            if (
                onRowAction == null &&
                rowDetailDrawer &&
                isEmbeddedTableEditRowAction(payload.action)
            ) {
                openDetailDrawerForRow(resolveRowIdFromReference(payload.row));
                return;
            }
            handleRelationshipRowAction(payload);
        },
        [
            onRowAction,
            rowDetailDrawer,
            resolveRowIdFromReference,
            openDetailDrawerForRow,
            handleRelationshipRowAction,
        ],
    );

    const columnDefs = React.useMemo(
        () =>
            buildDataTableColumnDefs(schemaColumns, {
                hasBulkActions,
                reorderable: isReorderable,
                onCellChange: handleCellChange,
                onRowReplace: handleRowReplace,
                onRowAction: handleRowAction,
                requireRowIdForActions,
            }),
        [
            schemaColumns,
            hasBulkActions,
            isReorderable,
            handleCellChange,
            handleRowReplace,
            handleRowAction,
            requireRowIdForActions,
        ],
    );

    const getColumnCanGlobalFilter = React.useCallback(
        (column: Column<Record<string, unknown>, unknown>) => {
            const col = schemaColumns.find((c) => c.id === column.id);
            return col?.searchable === true;
        },
        [schemaColumns],
    );

    const handleRowSelectionChange = React.useCallback(
        (updater: React.SetStateAction<RowSelectionState>) => {
            setRowSelection((prev) => {
                const next = functionalUpdate(updater, prev);
                if (
                    isAllRowsSelected &&
                    Object.keys(next).length < data.length
                ) {
                    setIsAllRowsSelected(false);
                }
                return next;
            });
        },
        [data.length, isAllRowsSelected],
    );

    const table = useReactTable({
        data,
        columns: columnDefs,
        state: {
            sorting,
            columnVisibility,
            columnOrder,
            rowSelection,
            columnFilters,
            globalFilter,
            pagination: paginationState,
        },
        getRowId: (row, index) => getStableRowId(row, index),
        enableRowSelection: hasBulkActions,
        onRowSelectionChange: handleRowSelectionChange,
        onSortingChange: handleSortingChange,
        onColumnFiltersChange: setColumnFilters,
        onGlobalFilterChange: setGlobalFilter,
        onColumnVisibilityChange: setColumnVisibility,
        onColumnOrderChange: setColumnOrder,
        onPaginationChange: handlePaginationChange,
        manualPagination: serverPagination != null,
        manualFiltering: serverPagination != null,
        manualSorting: serverPagination != null,
        pageCount:
            serverPagination != null ? serverPagination.last_page : undefined,
        rowCount: serverPagination != null ? serverPagination.total : undefined,
        globalFilterFn: 'includesString',
        getColumnCanGlobalFilter,
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getSortedRowModel: getSortedRowModel(),
        ...(serverPagination == null
            ? { getPaginationRowModel: getPaginationRowModel() }
            : {}),
        getFacetedRowModel: getFacetedRowModel(),
        getFacetedUniqueValues: getFacetedUniqueValues(),
    });

    const sortableRows = React.useMemo(
        () =>
            data.map((row, index) => ({
                ...row,
                id: getStableRowId(row, index),
            })),
        [data, getStableRowId],
    );
    const sortable = useSortable<
        Array<Record<string, unknown> & { id: string }>[number]
    >(sortableRows, {
        sortColumn: reorderKey ?? 'sort_order',
        endpoint: (item) =>
            reorderEndpoint != null ? reorderEndpoint(item) : '',
        reindexOnReorder: serverPagination == null,
        onError: () => {
            if (reorderOnError != null) {
                reorderOnError();
                return;
            }
            toast.error('Reorder failed to persist');
        },
        onItemsChange: (next) => {
            setData(next);
        },
    });
    const isReorderSortDesc = React.useMemo(() => {
        if (reorderKey == null) {
            return false;
        }
        const activeSort = sorting[0];
        return activeSort?.id === reorderKey && activeSort.desc === true;
    }, [reorderKey, sorting]);
    const hasNonReorderSorting = React.useMemo(() => {
        if (reorderKey == null) {
            return false;
        }
        const activeSort = sorting[0];
        if (activeSort == null) {
            return false;
        }

        return activeSort.id !== reorderKey;
    }, [reorderKey, sorting]);

    const handleDragEnd = React.useCallback(
        (event: DragEndEvent) => {
            if (!isReorderable || event.over == null) {
                return;
            }
            if (hasNonReorderSorting) {
                toast.error(
                    `Reordering is available only when table is sorted by '${reorderKey ?? 'sort_order'}'.`,
                );
                return;
            }
            if (event.active.id === event.over.id) {
                return;
            }
            const tableRows = table.getRowModel().rows;
            const newIndex = tableRows.findIndex(
                (row) => row.id === String(event.over?.id),
            );
            if (newIndex < 0) {
                return;
            }
            const pagination = table.getState().pagination;
            const pageOffset = pagination.pageIndex * pagination.pageSize;
            const ascPosition = pageOffset + newIndex + 1;
            const totalCount = serverPagination?.total ?? sortable.items.length;
            const targetPosition = isReorderSortDesc
                ? totalCount - pageOffset - newIndex
                : ascPosition;
            const localPosition = newIndex + 1;
            sortable.handleReorder(
                event.active.id,
                localPosition,
                targetPosition,
            );
        },
        [
            hasNonReorderSorting,
            isReorderSortDesc,
            isReorderable,
            reorderKey,
            serverPagination?.total,
            sortable,
            table,
        ],
    );

    const tableLabelId = `${id}-table-label`;
    const handleSelectAllRows = React.useCallback(() => {
        if (serverPagination == null) {
            table.toggleAllRowsSelected(true);
            return;
        }
        setIsAllRowsSelected(true);
        setRowSelection((prev) => {
            const next = { ...prev };
            for (const [index, row] of data.entries()) {
                next[getStableRowId(row, index)] = true;
            }
            return next;
        });
    }, [data, getStableRowId, serverPagination, table]);
    const handleDeselectAllRows = React.useCallback(() => {
        setIsAllRowsSelected(false);
        setRowSelection({});
        table.toggleAllRowsSelected(false);
    }, [table]);
    const handleBulkActionClick = React.useCallback(
        (actionId: string) =>
            handleRelationshipBulkAction(actionId, handleDeselectAllRows),
        [handleDeselectAllRows, handleRelationshipBulkAction],
    );
    const selectedRowCount = isAllRowsSelected
        ? (serverPagination?.total ?? table.getFilteredRowModel().rows.length)
        : Object.keys(rowSelection).length;
    const totalRowCount =
        serverPagination?.total ?? table.getFilteredRowModel().rows.length;

    const rowCountLabel = serverPagination
        ? serverPagination.total === 0
            ? '0 row(s).'
            : serverPagination.from != null && serverPagination.to != null
              ? `${serverPagination.from}–${serverPagination.to} of ${serverPagination.total} row(s).`
              : `${serverPagination.total} row(s).`
        : `${table.getFilteredRowModel().rows.length} row(s).`;

    const tableAndFooter = (
        <DataTableBodyWithFooter
            id={id}
            table={table}
            isReorderable={isReorderable}
            onRowClick={
                onRowClick != null ||
                (rowDetailDrawer && openDetailDrawerOnRowClick)
                    ? handleRowClick
                    : undefined
            }
            emptyColSpan={columnDefs.length}
            rowCountLabel={rowCountLabel}
            pagination={paginationVisibility}
            onDragEnd={handleDragEnd}
            rowValidationMessagesById={rowValidationMessagesById}
            isReordering={sortable.isReordering}
        />
    );

    return {
        id,
        table,
        hasToolbarActions,
        toolbarActions,
        handleToolbarActionClick,
        toolbarActionsDisabled,
        toolbarActionsDisabledTitle,
        hasBulkActions,
        selectedRowCount,
        isAllRowsSelected,
        totalRowCount,
        handleSelectAllRows,
        handleDeselectAllRows,
        bulkActions,
        handleBulkActionClick,
        hasSearchableColumns,
        hasFilters,
        globalFilter,
        setGlobalFilter,
        serverFilters,
        serverFilterState,
        setSingleServerFilter,
        toggleMultiServerFilterValue,
        setDateServerFilter,
        tableAndFooter,
        rowDetailDrawer,
        detailDrawerOpen,
        detailDrawerRow,
        detailDrawerRowId,
        detailDrawerTitleColumn,
        schemaColumns,
        handleDetailDrawerOpenChange,
        handleRowReplace,
        pendingEmbeddedRowConfirm,
        dismissPendingRowActionConfirm,
        confirmPendingRowAction,
        tableLabelId,
        detailDrawerBodyVariant,
        renderRowDrawerAttachBody,
        rowValidationMessagesById,
        rowValidationFieldErrorsById,
    };
}
