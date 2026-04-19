import {
    closestCenter,
    DndContext,
    KeyboardSensor,
    MouseSensor,
    TouchSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import { restrictToVerticalAxis } from '@dnd-kit/modifiers';
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
    useReactTable,
    type VisibilityState,
} from '@tanstack/react-table';
import * as React from 'react';
import { toast } from 'sonner';
import { FlatpackConfirmDialog } from '@/components/flatpack/flatpack-confirm-dialog';
import { DataTableBody } from '@/components/table/data-table-body';
import { buildDataTableColumnDefs } from '@/components/table/data-table-column-defs';
import {
    leafColumnIdsInSchemaOrder,
    visibilityFromSchema,
} from '@/components/table/data-table-column-visibility';
import {
    DATA_TABLE_LABEL,
    DATA_TABLE_ROW_CLICK_IGNORE_SELECTOR,
} from '@/components/table/data-table-constants';
import { DataTableFooter } from '@/components/table/data-table-footer';
import { DataTableRowDrawerPanel } from '@/components/table/data-table-row-drawer';
import { DataTableToolbar } from '@/components/table/data-table-toolbar';
import { useDataTableReorder } from '@/hooks/use-data-table-reorder';
import { useDataTableServerState } from '@/hooks/use-data-table-server-state';
import {
    deferNotifyParentFormValues,
    stableRowId,
} from '@/lib/data-table-utils';
import { DEFAULT_LIST_ROW_REORDER_COLUMN } from '@/lib/generated/composition-schema-keys';
import { cn } from '@/lib/utils';
import type {
    DataTableProps,
    DataTableRowActionPayload,
} from '@/types/data-table';

export { buildDataTableColumnDefs } from '@/components/table/data-table-column-defs';
export type {
    DataTableProps,
    FlatpackListServerPagination,
} from '@/types/data-table';

/** Embedded form relation tables use {@code remove} vs list {@code delete} — same client row-drop behavior. */
function isInlineRelationRemovalAction(actionKey: string | undefined): boolean {
    const a = actionKey?.toLowerCase();
    return a === 'delete' || a === 'remove';
}

export function DataTable({
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
    reorderable: reorderableProp,
    onRowClick,
    onValueChange,
    onBulkAction,
    onRowAction,
    onCellUpdate,
    onRowUpdate,
    className,
    serverPagination,
    serverSearch,
    serverFilters = [],
    serverFilterValues = {},
    serverSorting = { sort_by: null, sort_direction: null },
    onServerPaginationChange,
}: DataTableProps) {
    const hasToolbarActions = toolbarActions.length > 0;

    const handleToolbarActionClick = React.useCallback(
        (actionId: string) => {
            onToolbarAction?.(actionId);
        },
        [onToolbarAction],
    );

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
    const [detailDrawerOpen, setDetailDrawerOpen] = React.useState(false);
    const [detailDrawerRowId, setDetailDrawerRowId] = React.useState<
        string | null
    >(null);
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
        setSorting,
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
        onServerPaginationChange,
    });

    const handleCellChange = React.useCallback(
        (rowId: string, columnId: string, next: unknown) => {
            let previousRow: Record<string, unknown> | null = null;
            let nextRow: Record<string, unknown> | null = null;
            setData((prev) => {
                const idx = prev.findIndex(
                    (row, index) => getStableRowId(row, index) === rowId,
                );
                if (idx === -1) {
                    return prev;
                }
                const cur = prev[idx][columnId];
                if (Object.is(cur, next)) {
                    return prev;
                }
                previousRow = prev[idx];
                nextRow = { ...prev[idx], [columnId]: next };
                const resolvedNextRow = nextRow;
                const nextRows = prev.map((r, i) =>
                    i === idx ? resolvedNextRow : r,
                );
                deferNotifyParentFormValues(onValueChange, nextRows);
                return nextRows;
            });
            if (
                nextRow == null ||
                previousRow == null ||
                onCellUpdate == null
            ) {
                return;
            }
            void Promise.resolve(
                onCellUpdate({
                    rowId,
                    row: nextRow,
                    columnId,
                    value: next,
                }),
            ).catch(() => {
                setData((prev) => {
                    const idx = prev.findIndex(
                        (row, index) => getStableRowId(row, index) === rowId,
                    );
                    if (idx === -1) {
                        return prev;
                    }
                    const resolvedPreviousRow = previousRow;
                    if (resolvedPreviousRow == null) {
                        return prev;
                    }
                    const reverted = prev.map((r, i) =>
                        i === idx ? resolvedPreviousRow : r,
                    );
                    deferNotifyParentFormValues(onValueChange, reverted);
                    return reverted;
                });
            });
        },
        [getStableRowId, onCellUpdate, onValueChange],
    );

    const handleRowReplace = React.useCallback(
        (rowId: string, nextRow: Record<string, unknown>) => {
            let previousRow: Record<string, unknown> | null = null;
            setData((prev) => {
                const idx = prev.findIndex(
                    (row, index) => getStableRowId(row, index) === rowId,
                );
                if (idx === -1) {
                    return prev;
                }
                previousRow = prev[idx];
                const nextRows = prev.map((r, i) => (i === idx ? nextRow : r));
                deferNotifyParentFormValues(onValueChange, nextRows);
                return nextRows;
            });
            if (onRowUpdate == null) {
                return;
            }
            void Promise.resolve(
                onRowUpdate({
                    rowId,
                    row: nextRow,
                }),
            ).catch(() => {
                if (previousRow == null) {
                    return;
                }
                setData((prev) => {
                    const idx = prev.findIndex(
                        (row, index) => getStableRowId(row, index) === rowId,
                    );
                    if (idx === -1) {
                        return prev;
                    }
                    const resolvedPreviousRow = previousRow;
                    if (resolvedPreviousRow == null) {
                        return prev;
                    }
                    const reverted = prev.map((r, i) =>
                        i === idx ? resolvedPreviousRow : r,
                    );
                    deferNotifyParentFormValues(onValueChange, reverted);
                    return reverted;
                });
            });
        },
        [getStableRowId, onRowUpdate, onValueChange],
    );

    const detailDrawerTitleColumn = React.useMemo(() => {
        const cols = schemaColumns.filter((c) => c.type !== 'actions');
        return cols[0] ?? schemaColumns[0] ?? null;
    }, [schemaColumns]);

    const detailDrawerRow = React.useMemo(() => {
        if (!rowDetailDrawer || detailDrawerRowId == null) {
            return null;
        }
        const idx = data.findIndex(
            (r, i) => getStableRowId(r, i) === detailDrawerRowId,
        );
        return idx >= 0 ? data[idx] : null;
    }, [rowDetailDrawer, detailDrawerRowId, data, getStableRowId]);

    React.useEffect(() => {
        if (detailDrawerOpen && detailDrawerRow == null) {
            setDetailDrawerOpen(false);
            setDetailDrawerRowId(null);
        }
    }, [detailDrawerOpen, detailDrawerRow]);

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
            if (rowDetailDrawer) {
                const idx = data.indexOf(row);
                const rowId =
                    idx >= 0
                        ? getStableRowId(row, idx)
                        : getStableRowId(row, 0);
                setDetailDrawerRowId(rowId);
                setDetailDrawerOpen(true);
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
        [rowDetailDrawer, data, getStableRowId, onRowClick],
    );

    const resolveEmbeddedRowRemoval = React.useCallback(
        (payload: DataTableRowActionPayload) => {
            setData((prev) => {
                const keyVal = payload.row[dataRowKey];
                let targetStable: string | null = null;
                if (keyVal != null && keyVal !== '') {
                    targetStable = String(keyVal);
                } else {
                    const idx = prev.indexOf(payload.row);
                    if (idx !== -1) {
                        targetStable = getStableRowId(prev[idx], idx);
                    }
                }
                if (targetStable === null) {
                    return prev;
                }
                const nextRows = prev.filter(
                    (row, index) => getStableRowId(row, index) !== targetStable,
                );
                deferNotifyParentFormValues(onValueChange, nextRows);
                return nextRows;
            });
        },
        [dataRowKey, getStableRowId, onValueChange],
    );

    const [pendingEmbeddedRowConfirm, setPendingEmbeddedRowConfirm] =
        React.useState<DataTableRowActionPayload | null>(null);

    const applyEmbeddedDestructiveRowAction = React.useCallback(
        (payload: DataTableRowActionPayload) => {
            const a = payload.action.toLowerCase();
            if (a !== 'delete' && a !== 'remove') {
                return;
            }
            resolveEmbeddedRowRemoval(payload);
            const msg = payload.button?.success_message;
            if (typeof msg === 'string' && msg.trim() !== '') {
                toast.success(msg.trim());
            }
        },
        [resolveEmbeddedRowRemoval],
    );

    const effectiveOnRowAction = React.useCallback(
        (payload: DataTableRowActionPayload) => {
            if (onRowAction != null) {
                void Promise.resolve(onRowAction(payload));
                return;
            }
            if (onValueChange == null) {
                return;
            }
            const a = payload.action.toLowerCase();
            if (a !== 'delete' && a !== 'remove') {
                return;
            }
            if (payload.button?.confirm === true) {
                setPendingEmbeddedRowConfirm(payload);
                return;
            }
            applyEmbeddedDestructiveRowAction(payload);
        },
        [onRowAction, onValueChange, applyEmbeddedDestructiveRowAction],
    );

    const columnDefs = React.useMemo(
        () =>
            buildDataTableColumnDefs(schemaColumns, {
                hasBulkActions,
                reorderable: isReorderable,
                onCellChange: handleCellChange,
                onRowReplace: handleRowReplace,
                onRowAction: effectiveOnRowAction,
            }),
        [
            schemaColumns,
            hasBulkActions,
            isReorderable,
            handleCellChange,
            handleRowReplace,
            effectiveOnRowAction,
        ],
    );

    const getColumnCanGlobalFilter = React.useCallback(
        (column: Column<Record<string, unknown>, unknown>) => {
            const col = schemaColumns.find((c) => c.id === column.id);
            return col?.searchable === true;
        },
        [schemaColumns],
    );

    const dndSensors = useSensors(
        useSensor(MouseSensor, {}),
        useSensor(TouchSensor, {}),
        useSensor(KeyboardSensor, {}),
    );
    const dndId = React.useId();
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

    const { handleDragEnd } = useDataTableReorder({
        data,
        table,
        reorderKey,
        onValueChange,
        onReorderApplied: setData,
        onReorderCompleted: () => setSorting([]),
    });

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

    const handleBulkActionClick = React.useCallback(
        async (actionId: string) => {
            const selectedIds = new Set(
                Object.entries(rowSelection)
                    .filter(([, selected]) => selected)
                    .map(([rowId]) => rowId),
            );

            if (selectedIds.size === 0) {
                return;
            }

            const bulkConfig = bulkActions.find((b) => b.id === actionId);
            const actionKey = bulkConfig?.action?.trim();
            if (!actionKey) {
                return;
            }

            if (onBulkAction != null) {
                const previousData = data;
                const previousSelection = rowSelection;
                const previousIsAllRowsSelected = isAllRowsSelected;
                const optimisticRows =
                    actionKey === 'delete' && isAllRowsSelected
                        ? []
                        : actionKey === 'delete'
                          ? data.filter(
                                (row, index) =>
                                    !selectedIds.has(
                                        getStableRowId(row, index),
                                    ),
                            )
                          : data;

                if (actionKey === 'delete') {
                    setData(optimisticRows);
                    deferNotifyParentFormValues(onValueChange, optimisticRows);
                }
                handleDeselectAllRows();

                try {
                    await onBulkAction({
                        action: actionKey,
                        selection: isAllRowsSelected
                            ? 'all'
                            : Array.from(selectedIds),
                        search: globalFilter,
                        filters: serverFilterState,
                        sorting: serverSortingForBulkAction,
                    });
                    return;
                } catch (error) {
                    if (actionKey === 'delete') {
                        setData(previousData);
                    }
                    setRowSelection(previousSelection);
                    setIsAllRowsSelected(previousIsAllRowsSelected);
                    throw error;
                }
            }

            if (
                onBulkAction == null &&
                isInlineRelationRemovalAction(actionKey)
            ) {
                setData((prev) => {
                    const nextRows = prev.filter(
                        (row, index) =>
                            !selectedIds.has(getStableRowId(row, index)),
                    );
                    deferNotifyParentFormValues(onValueChange, nextRows);
                    return nextRows;
                });
                const bulkMsg = bulkConfig?.success_message;
                if (typeof bulkMsg === 'string' && bulkMsg.trim() !== '') {
                    queueMicrotask(() => {
                        toast.success(bulkMsg.trim());
                    });
                }
            }

            handleDeselectAllRows();
        },
        [
            bulkActions,
            data,
            globalFilter,
            getStableRowId,
            handleDeselectAllRows,
            isAllRowsSelected,
            onBulkAction,
            onValueChange,
            rowSelection,
            serverFilterState,
            serverSortingForBulkAction,
        ],
    );
    const paginationStateCurrent = table.getState().pagination;
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

    const tableBody = (
        <DataTableBody
            table={table}
            isReorderable={isReorderable}
            onRowClick={
                onRowClick != null || rowDetailDrawer
                    ? handleRowClick
                    : undefined
            }
            emptyColSpan={columnDefs.length}
        />
    );

    const tableAndFooter = (
        <>
            <div className="overflow-hidden rounded-lg border">
                {isReorderable ? (
                    <DndContext
                        id={dndId}
                        collisionDetection={closestCenter}
                        modifiers={[restrictToVerticalAxis]}
                        onDragEnd={handleDragEnd}
                        sensors={dndSensors}
                    >
                        {tableBody}
                    </DndContext>
                ) : (
                    tableBody
                )}
            </div>
            <DataTableFooter
                id={id}
                rowCountLabel={rowCountLabel}
                pageSize={paginationStateCurrent.pageSize}
                pageIndex={paginationStateCurrent.pageIndex}
                pageCount={table.getPageCount()}
                canPreviousPage={table.getCanPreviousPage()}
                canNextPage={table.getCanNextPage()}
                onPageSizeChange={(value) => {
                    table.setPageSize(Number(value));
                }}
                onFirstPage={() => table.setPageIndex(0)}
                onPreviousPage={() => table.previousPage()}
                onNextPage={() => table.nextPage()}
                onLastPage={() => table.setPageIndex(table.getPageCount() - 1)}
            />
        </>
    );

    return (
        <div
            className={cn('flex w-full flex-col gap-4', className)}
            role="region"
            aria-labelledby={tableLabelId}
        >
            <span id={tableLabelId} className="sr-only">
                {DATA_TABLE_LABEL}
            </span>
            <DataTableToolbar
                id={id}
                table={table}
                hasToolbarActions={hasToolbarActions}
                toolbarActions={toolbarActions}
                onToolbarAction={handleToolbarActionClick}
                toolbarActionsDisabled={toolbarActionsDisabled}
                toolbarActionsDisabledTitle={toolbarActionsDisabledTitle}
                hasBulkActions={hasBulkActions}
                selectedRowCount={selectedRowCount}
                isAllRowsSelected={isAllRowsSelected}
                totalRowCount={totalRowCount}
                onSelectAllRows={handleSelectAllRows}
                onDeselectAllRows={handleDeselectAllRows}
                bulkActions={bulkActions}
                onBulkAction={handleBulkActionClick}
                hasSearchableColumns={hasSearchableColumns}
                hasFilters={hasFilters}
                globalFilter={globalFilter}
                onGlobalFilterChange={setGlobalFilter}
                serverFilters={serverFilters}
                serverFilterState={serverFilterState}
                onSetSingleFilter={setSingleServerFilter}
                onToggleMultiFilterValue={toggleMultiServerFilterValue}
                onSetDateFilter={setDateServerFilter}
            />
            {tableAndFooter}
            {rowDetailDrawer &&
            detailDrawerOpen &&
            detailDrawerRow != null &&
            detailDrawerTitleColumn != null &&
            detailDrawerRowId != null ? (
                <DataTableRowDrawerPanel
                    open={detailDrawerOpen}
                    onOpenChange={(open) => {
                        setDetailDrawerOpen(open);
                        if (!open) {
                            setDetailDrawerRowId(null);
                        }
                    }}
                    row={detailDrawerRow}
                    rowId={detailDrawerRowId}
                    schemaColumns={schemaColumns}
                    titleColumn={detailDrawerTitleColumn}
                    onRowReplace={handleRowReplace}
                />
            ) : null}
            <FlatpackConfirmDialog
                open={pendingEmbeddedRowConfirm !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingEmbeddedRowConfirm(null);
                    }
                }}
                title={pendingEmbeddedRowConfirm?.button?.label ?? 'Confirm'}
                continueVariant={
                    pendingEmbeddedRowConfirm?.button?.variant === 'destructive'
                        ? 'destructive'
                        : 'default'
                }
                onContinue={() => {
                    const payload = pendingEmbeddedRowConfirm;
                    setPendingEmbeddedRowConfirm(null);
                    if (payload !== null) {
                        applyEmbeddedDestructiveRowAction(payload);
                    }
                }}
            />
        </div>
    );
}
