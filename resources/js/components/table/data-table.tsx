import {
    closestCenter,
    DndContext,
    type DragEndEvent,
    KeyboardSensor,
    MouseSensor,
    TouchSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import { restrictToVerticalAxis } from '@dnd-kit/modifiers';
import {
    arrayMove,
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import {
    type ColumnFiltersState,
    flexRender,
    getCoreRowModel,
    getFacetedRowModel,
    getFacetedUniqueValues,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    type SortingState,
    useReactTable,
    type VisibilityState,
} from '@tanstack/react-table';
import {
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronsLeftIcon,
    ChevronsRightIcon,
    Columns3Icon,
} from 'lucide-react';
import * as React from 'react';
import { buildDataTableColumnDefs } from '@/components/table/data-table-column-defs';
import {
    columnVisibilityMenuLabel,
    leafColumnIdsInSchemaOrder,
    visibilityFromSchema,
} from '@/components/table/data-table-column-visibility';
import { DataTableDraggableRow } from '@/components/table/data-table-draggable-row';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { TabsContent } from '@/components/ui/tabs';
import { reindexReorderColumn, stableRowId } from '@/lib/data-table-utils';
import { cn } from '@/lib/utils';
import type { DataTableProps } from '@/types/data-table';

export { buildDataTableColumnDefs } from '@/components/table/data-table-column-defs';
export type { DataTableProps } from '@/types/data-table';

/**
 * TanStack table driven by a Flatpack column schema and row `data` (plain objects).
 */
export function DataTable({
    id,
    columns: schemaColumns,
    data: initialData,
    checkboxes = false,
    reorderable: reorderableProp,
    onValueChange,
    className,
    toolbarStart,
    toolbarAfterColumns,
    primaryTabPanelValue,
    tabPanels,
}: DataTableProps) {
    const reorderKey =
        reorderableProp === true
            ? 'sort_order'
            : typeof reorderableProp === 'string'
              ? reorderableProp
              : null;
    const isReorderable = reorderKey !== null;

    const [data, setData] = React.useState<Record<string, unknown>[]>(
        () => initialData,
    );
    React.useLayoutEffect(() => {
        setData(initialData);
    }, [initialData]);

    const [rowSelection, setRowSelection] = React.useState({});
    const [columnVisibility, setColumnVisibility] =
        React.useState<VisibilityState>(() =>
            visibilityFromSchema(schemaColumns),
        );
    const schemaLeafOrder = React.useMemo(
        () =>
            leafColumnIdsInSchemaOrder(
                schemaColumns,
                checkboxes,
                isReorderable,
            ),
        [schemaColumns, checkboxes, isReorderable],
    );
    const [columnOrder, setColumnOrder] = React.useState<string[]>(() =>
        leafColumnIdsInSchemaOrder(schemaColumns, checkboxes, isReorderable),
    );
    React.useLayoutEffect(() => {
        setColumnOrder(schemaLeafOrder);
    }, [schemaLeafOrder]);
    const [columnFilters, setColumnFilters] =
        React.useState<ColumnFiltersState>([]);
    const [sorting, setSorting] = React.useState<SortingState>([]);
    const [pagination, setPagination] = React.useState({
        pageIndex: 0,
        pageSize: 10,
    });

    const handleCellChange = React.useCallback(
        (rowId: string, columnId: string, next: unknown) => {
            setData((prev) => {
                const idx = prev.findIndex(
                    (row, index) => stableRowId(row, index) === rowId,
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
        [onValueChange],
    );

    const handleRowReplace = React.useCallback(
        (rowId: string, nextRow: Record<string, unknown>) => {
            setData((prev) => {
                const idx = prev.findIndex(
                    (row, index) => stableRowId(row, index) === rowId,
                );
                if (idx === -1) {
                    return prev;
                }
                const nextRows = prev.map((r, i) => (i === idx ? nextRow : r));
                onValueChange?.(nextRows);
                return nextRows;
            });
        },
        [onValueChange],
    );

    const columnDefs = React.useMemo(
        () =>
            buildDataTableColumnDefs(schemaColumns, {
                checkboxes,
                reorderable: isReorderable,
                onCellChange: handleCellChange,
                onRowReplace: handleRowReplace,
            }),
        [
            schemaColumns,
            checkboxes,
            isReorderable,
            handleCellChange,
            handleRowReplace,
        ],
    );

    const dndSensors = useSensors(
        useSensor(MouseSensor, {}),
        useSensor(TouchSensor, {}),
        useSensor(KeyboardSensor, {}),
    );
    const dndId = React.useId();

    const table = useReactTable({
        data,
        columns: columnDefs,
        state: {
            sorting,
            columnVisibility,
            columnOrder,
            rowSelection,
            columnFilters,
            pagination,
        },
        getRowId: (row, index) => stableRowId(row, index),
        enableRowSelection: checkboxes,
        onRowSelectionChange: setRowSelection,
        onSortingChange: setSorting,
        onColumnFiltersChange: setColumnFilters,
        onColumnVisibilityChange: setColumnVisibility,
        onColumnOrderChange: setColumnOrder,
        onPaginationChange: setPagination,
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getFacetedRowModel: getFacetedRowModel(),
        getFacetedUniqueValues: getFacetedUniqueValues(),
    });

    const handleDragEnd = React.useCallback(
        (event: DragEndEvent) => {
            if (!reorderKey) {
                return;
            }
            const { active, over } = event;
            if (!over || active.id === over.id) {
                return;
            }
            const pageRows = table.getRowModel().rows;
            const pageRowIds = pageRows.map((r) => r.id);
            const oldPageIdx = pageRowIds.indexOf(String(active.id));
            const newPageIdx = pageRowIds.indexOf(String(over.id));
            if (oldPageIdx === -1 || newPageIdx === -1) {
                return;
            }
            const fullIndices = pageRowIds.map((rowId) =>
                data.findIndex((row, idx) => stableRowId(row, idx) === rowId),
            );
            if (fullIndices.some((i) => i < 0)) {
                return;
            }
            const pageSlice: Record<string, unknown>[] = [];
            for (const i of fullIndices) {
                const row = data[i];
                if (row === undefined) {
                    return;
                }
                pageSlice.push(row);
            }
            const reorderedPage = arrayMove(pageSlice, oldPageIdx, newPageIdx);
            const next = [...data];
            for (let p = 0; p < fullIndices.length; p++) {
                const moved = reorderedPage[p];
                if (moved === undefined) {
                    return;
                }
                next[fullIndices[p]] = moved;
            }
            const withOrder = reindexReorderColumn(next, reorderKey);
            setData(withOrder);
            setSorting([]);
            onValueChange?.(withOrder);
        },
        [data, onValueChange, reorderKey, table],
    );

    const tableLabelId = `${id}-table-label`;

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
                        <Table>
                            <TableHeader className="sticky top-0 z-10 bg-muted">
                                {table.getHeaderGroups().map((headerGroup) => (
                                    <TableRow key={headerGroup.id}>
                                        {headerGroup.headers.map((header) => (
                                            <TableHead
                                                key={header.id}
                                                colSpan={header.colSpan}
                                            >
                                                {header.isPlaceholder
                                                    ? null
                                                    : flexRender(
                                                          header.column
                                                              .columnDef.header,
                                                          header.getContext(),
                                                      )}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                ))}
                            </TableHeader>
                            <TableBody>
                                {table.getRowModel().rows?.length ? (
                                    <SortableContext
                                        items={table
                                            .getRowModel()
                                            .rows.map((r) => r.id)}
                                        strategy={verticalListSortingStrategy}
                                    >
                                        {table.getRowModel().rows.map((row) => (
                                            <DataTableDraggableRow
                                                key={row.id}
                                                row={row}
                                            />
                                        ))}
                                    </SortableContext>
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={columnDefs.length}
                                            className="h-24 text-center"
                                        >
                                            No results.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </DndContext>
                ) : (
                    <Table>
                        <TableHeader className="sticky top-0 z-10 bg-muted">
                            {table.getHeaderGroups().map((headerGroup) => (
                                <TableRow key={headerGroup.id}>
                                    {headerGroup.headers.map((header) => (
                                        <TableHead
                                            key={header.id}
                                            colSpan={header.colSpan}
                                        >
                                            {header.isPlaceholder
                                                ? null
                                                : flexRender(
                                                      header.column.columnDef
                                                          .header,
                                                      header.getContext(),
                                                  )}
                                        </TableHead>
                                    ))}
                                </TableRow>
                            ))}
                        </TableHeader>
                        <TableBody>
                            {table.getRowModel().rows?.length ? (
                                table.getRowModel().rows.map((row) => (
                                    <TableRow
                                        key={row.id}
                                        data-state={
                                            row.getIsSelected() && 'selected'
                                        }
                                    >
                                        {row.getVisibleCells().map((cell) => (
                                            <TableCell key={cell.id}>
                                                {flexRender(
                                                    cell.column.columnDef.cell,
                                                    cell.getContext(),
                                                )}
                                            </TableCell>
                                        ))}
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={columnDefs.length}
                                        className="h-24 text-center"
                                    >
                                        No results.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                )}
            </div>

            <div className="flex flex-col gap-4 px-1 sm:flex-row sm:items-center sm:justify-between">
                {checkboxes ? (
                    <div className="text-sm text-muted-foreground">
                        {table.getFilteredSelectedRowModel().rows.length} of{' '}
                        {table.getFilteredRowModel().rows.length} row(s)
                        selected.
                    </div>
                ) : (
                    <div className="text-sm text-muted-foreground">
                        {table.getFilteredRowModel().rows.length} row(s).
                    </div>
                )}
                <div className="flex w-full flex-col gap-4 sm:w-auto sm:flex-row sm:items-center sm:gap-6">
                    <div className="flex items-center gap-2">
                        <Label
                            htmlFor={`${id}-rows-per-page`}
                            className="text-sm font-medium whitespace-nowrap"
                        >
                            Rows per page
                        </Label>
                        <Select
                            value={`${table.getState().pagination.pageSize}`}
                            onValueChange={(value) => {
                                table.setPageSize(Number(value));
                            }}
                        >
                            <SelectTrigger
                                size="sm"
                                className="w-20"
                                id={`${id}-rows-per-page`}
                            >
                                <SelectValue
                                    placeholder={
                                        table.getState().pagination.pageSize
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent side="top">
                                <SelectGroup>
                                    {[10, 20, 30, 40, 50].map((pageSize) => (
                                        <SelectItem
                                            key={pageSize}
                                            value={`${pageSize}`}
                                        >
                                            {pageSize}
                                        </SelectItem>
                                    ))}
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="flex items-center justify-center gap-2 text-sm font-medium">
                        <span className="whitespace-nowrap">
                            Page {table.getState().pagination.pageIndex + 1} of{' '}
                            {table.getPageCount() || 1}
                        </span>
                        <div className="flex items-center gap-1">
                            <Button
                                variant="outline"
                                className="hidden size-8 p-0 sm:flex"
                                onClick={() => table.setPageIndex(0)}
                                disabled={!table.getCanPreviousPage()}
                            >
                                <span className="sr-only">First page</span>
                                <ChevronsLeftIcon className="size-4" />
                            </Button>
                            <Button
                                variant="outline"
                                className="size-8"
                                size="icon"
                                onClick={() => table.previousPage()}
                                disabled={!table.getCanPreviousPage()}
                            >
                                <span className="sr-only">Previous page</span>
                                <ChevronLeftIcon className="size-4" />
                            </Button>
                            <Button
                                variant="outline"
                                className="size-8"
                                size="icon"
                                onClick={() => table.nextPage()}
                                disabled={!table.getCanNextPage()}
                            >
                                <span className="sr-only">Next page</span>
                                <ChevronRightIcon className="size-4" />
                            </Button>
                            <Button
                                variant="outline"
                                className="hidden size-8 sm:flex"
                                size="icon"
                                onClick={() =>
                                    table.setPageIndex(table.getPageCount() - 1)
                                }
                                disabled={!table.getCanNextPage()}
                            >
                                <span className="sr-only">Last page</span>
                                <ChevronsRightIcon className="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );

    const primaryPanel =
        primaryTabPanelValue != null ? (
            <TabsContent
                value={primaryTabPanelValue}
                className="relative flex flex-col gap-4 overflow-auto outline-none"
            >
                {tableAndFooter}
            </TabsContent>
        ) : (
            tableAndFooter
        );

    return (
        <div
            className={cn('flex w-full flex-col gap-4', className)}
            role="region"
            aria-labelledby={tableLabelId}
        >
            <span id={tableLabelId} className="sr-only">
                Data table
            </span>
            <div
                className={cn(
                    'flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center',
                    toolbarStart != null
                        ? 'sm:justify-between'
                        : 'sm:justify-end',
                )}
            >
                {toolbarStart != null ? (
                    <div className="flex min-w-0 flex-col gap-2 @4xl/main:flex-row @4xl/main:items-center">
                        {toolbarStart}
                    </div>
                ) : null}
                <div className="flex items-center justify-end gap-2">
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" size="sm">
                                <Columns3Icon data-icon="inline-start" />
                                Columns
                                <ChevronDownIcon data-icon="inline-end" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-48">
                            {table
                                .getAllColumns()
                                .filter(
                                    (column) =>
                                        typeof column.accessorFn !==
                                            'undefined' && column.getCanHide(),
                                )
                                .map((column) => (
                                    <DropdownMenuCheckboxItem
                                        key={column.id}
                                        checked={column.getIsVisible()}
                                        onCheckedChange={(value) =>
                                            column.toggleVisibility(!!value)
                                        }
                                    >
                                        {columnVisibilityMenuLabel(column)}
                                    </DropdownMenuCheckboxItem>
                                ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                    {toolbarAfterColumns}
                </div>
            </div>

            {primaryPanel}
            {tabPanels}
        </div>
    );
}
