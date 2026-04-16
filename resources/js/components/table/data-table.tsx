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
import { DataTableBody } from '@/components/table/data-table-body';
import { buildDataTableColumnDefs } from '@/components/table/data-table-column-defs';
import {
    leafColumnIdsInSchemaOrder,
    visibilityFromSchema,
} from '@/components/table/data-table-column-visibility';
import { DATA_TABLE_LABEL } from '@/components/table/data-table-constants';
import { DataTableFooter } from '@/components/table/data-table-footer';
import { DataTableToolbar } from '@/components/table/data-table-toolbar';
import { useDataTableReorder } from '@/hooks/use-data-table-reorder';
import { useDataTableServerState } from '@/hooks/use-data-table-server-state';
import { stableRowId } from '@/lib/data-table-utils';
import { cn } from '@/lib/utils';
import type { DataTableProps } from '@/types/data-table';

export { buildDataTableColumnDefs } from '@/components/table/data-table-column-defs';
export type {
    DataTableProps,
    FlatpackListServerPagination,
} from '@/types/data-table';

export function DataTable({
    id,
    columns: schemaColumns,
    data: initialData,
    dataRowKey = 'id',
    bulkActions = [],
    reorderable: reorderableProp,
    onRowClick,
    onValueChange,
    onBulkAction,
    className,
    serverPagination,
    serverSearch,
    serverFilters = [],
    serverFilterValues = {},
    serverSorting = { sort_by: null, sort_direction: null },
    onServerPaginationChange,
}: DataTableProps) {
    const rowClickInteractiveSelector =
        'a,button,input,select,textarea,[role="button"],[role="checkbox"],[data-no-row-click]';

    const handleRowClick = React.useCallback(
        (
            event: React.MouseEvent<HTMLTableRowElement>,
            row: Record<string, unknown>,
        ) => {
            if (onRowClick == null) {
                return;
            }
            const target = event.target;
            if (!(target instanceof Element)) {
                onRowClick(row);
                return;
            }
            if (target.closest(rowClickInteractiveSelector)) {
                return;
            }
            onRowClick(row);
        },
        [onRowClick],
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
            ? 'sort_order'
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
                const nextRows = prev.map((r, i) =>
                    i === idx ? { ...r, [columnId]: next } : r,
                );
                onValueChange?.(nextRows);
                return nextRows;
            });
        },
        [getStableRowId, onValueChange],
    );

    const handleRowReplace = React.useCallback(
        (rowId: string, nextRow: Record<string, unknown>) => {
            setData((prev) => {
                const idx = prev.findIndex(
                    (row, index) => getStableRowId(row, index) === rowId,
                );
                if (idx === -1) {
                    return prev;
                }
                const nextRows = prev.map((r, i) => (i === idx ? nextRow : r));
                onValueChange?.(nextRows);
                return nextRows;
            });
        },
        [getStableRowId, onValueChange],
    );

    const columnDefs = React.useMemo(
        () =>
            buildDataTableColumnDefs(schemaColumns, {
                hasBulkActions,
                reorderable: isReorderable,
                onCellChange: handleCellChange,
                onRowReplace: handleRowReplace,
            }),
        [
            schemaColumns,
            hasBulkActions,
            isReorderable,
            handleCellChange,
            handleRowReplace,
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

    const handleBulkActionClick = React.useCallback(async (actionId: string) => {
        const selectedIds = new Set(
            Object.entries(rowSelection)
                .filter(([, selected]) => selected)
                .map(([rowId]) => rowId),
        );

        if (selectedIds.size === 0) {
            return;
        }

        const actionKey = bulkActions.find(
            (bulkAction) => bulkAction.id === actionId,
        )?.action;
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
                                !selectedIds.has(getStableRowId(row, index)),
                        )
                      : data;

            if (actionKey === 'delete') {
                setData(optimisticRows);
                onValueChange?.(optimisticRows);
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

        if (actionKey === 'delete') {
            setData((prev) => {
                const nextRows = prev.filter(
                    (row, index) => !selectedIds.has(getStableRowId(row, index)),
                );
                onValueChange?.(nextRows);
                return nextRows;
            });
        }

        handleDeselectAllRows();
    }, [
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
    ]);
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
            onRowClick={onRowClick ? handleRowClick : undefined}
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
        </div>
    );
}
