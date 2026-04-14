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
    type Column,
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
import { format } from 'date-fns';
import {
    CalendarIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronsLeftIcon,
    ChevronsRightIcon,
    Columns3Icon,
    SearchIcon,
    XIcon,
} from 'lucide-react';
import * as React from 'react';
import { buildDataTableColumnDefs } from '@/components/table/data-table-column-defs';
import {
    columnVisibilityMenuLabel,
    leafColumnIdsInSchemaOrder,
    visibilityFromSchema,
} from '@/components/table/data-table-column-visibility';
import {
    DATA_TABLE_DRAG_COLUMN_HEAD_CLASS,
    DATA_TABLE_EMPTY_RESULTS_LABEL,
    DATA_TABLE_LABEL,
    DATA_TABLE_PAGE_SIZE_OPTIONS,
    DATA_TABLE_SEARCH_PLACEHOLDER,
} from '@/components/table/data-table-constants';
import { DataTableDraggableRow } from '@/components/table/data-table-draggable-row';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
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
import { reindexReorderColumn, stableRowId } from '@/lib/data-table-utils';
import { cn } from '@/lib/utils';
import type { DataTableProps } from '@/types/data-table';
import type { DataTableFooterProps } from '@/types/table';

export { buildDataTableColumnDefs } from '@/components/table/data-table-column-defs';
export type {
    DataTableProps,
    FlatpackListServerPagination,
} from '@/types/data-table';

const ALL_FILTER_OPTION_VALUE = '__all__';

function DataTableFooter({
    id,
    rowCountLabel,
    pageSize,
    pageIndex,
    pageCount,
    canPreviousPage,
    canNextPage,
    onPageSizeChange,
    onFirstPage,
    onPreviousPage,
    onNextPage,
    onLastPage,
}: DataTableFooterProps) {
    return (
        <div className="flex flex-col gap-4 px-1 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-sm text-muted-foreground">{rowCountLabel}</div>
            <div className="flex w-full flex-col gap-4 sm:w-auto sm:flex-row sm:items-center sm:gap-6">
                <div className="flex items-center gap-2">
                    <Label
                        htmlFor={`${id}-rows-per-page`}
                        className="text-sm font-medium whitespace-nowrap"
                    >
                        Rows per page
                    </Label>
                    <Select
                        value={`${pageSize}`}
                        onValueChange={onPageSizeChange}
                    >
                        <SelectTrigger
                            size="sm"
                            className="w-20"
                            id={`${id}-rows-per-page`}
                        >
                            <SelectValue placeholder={pageSize} />
                        </SelectTrigger>
                        <SelectContent side="top">
                            <SelectGroup>
                                {DATA_TABLE_PAGE_SIZE_OPTIONS.map((option) => (
                                    <SelectItem
                                        key={option}
                                        value={`${option}`}
                                    >
                                        {option}
                                    </SelectItem>
                                ))}
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
                <div className="flex items-center justify-center gap-2 text-sm font-medium">
                    <span className="whitespace-nowrap">
                        Page {pageIndex + 1} of {pageCount || 1}
                    </span>
                    <div className="flex items-center gap-1">
                        <Button
                            variant="outline"
                            className="hidden size-8 p-0 sm:flex"
                            onClick={onFirstPage}
                            disabled={!canPreviousPage}
                        >
                            <span className="sr-only">First page</span>
                            <ChevronsLeftIcon className="size-4" />
                        </Button>
                        <Button
                            variant="outline"
                            className="size-8"
                            size="icon"
                            onClick={onPreviousPage}
                            disabled={!canPreviousPage}
                        >
                            <span className="sr-only">Previous page</span>
                            <ChevronLeftIcon className="size-4" />
                        </Button>
                        <Button
                            variant="outline"
                            className="size-8"
                            size="icon"
                            onClick={onNextPage}
                            disabled={!canNextPage}
                        >
                            <span className="sr-only">Next page</span>
                            <ChevronRightIcon className="size-4" />
                        </Button>
                        <Button
                            variant="outline"
                            className="hidden size-8 sm:flex"
                            size="icon"
                            onClick={onLastPage}
                            disabled={!canNextPage}
                        >
                            <span className="sr-only">Last page</span>
                            <ChevronsRightIcon className="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}

function normalizeServerFilterValues(
    values: Record<string, string | string[] | null> | undefined,
): Record<string, string | string[] | null> {
    if (!values) {
        return {};
    }
    const out: Record<string, string | string[] | null> = {};
    for (const [key, value] of Object.entries(values)) {
        if (Array.isArray(value)) {
            out[key] = value.map((v) => String(v)).filter((v) => v !== '');
            continue;
        }
        if (value == null) {
            out[key] = null;
            continue;
        }
        out[key] = String(value);
    }
    return out;
}

function filterValuesEqual(
    a: Record<string, string | string[] | null>,
    b: Record<string, string | string[] | null>,
): boolean {
    const keys = new Set([...Object.keys(a), ...Object.keys(b)]);
    for (const key of keys) {
        const av = a[key];
        const bv = b[key];
        if (Array.isArray(av) || Array.isArray(bv)) {
            if (
                !Array.isArray(av) ||
                !Array.isArray(bv) ||
                av.length !== bv.length
            ) {
                return false;
            }
            for (let i = 0; i < av.length; i++) {
                if (av[i] !== bv[i]) {
                    return false;
                }
            }
            continue;
        }
        if ((av ?? null) !== (bv ?? null)) {
            return false;
        }
    }
    return true;
}

function serverSortingFromState(sorting: SortingState): {
    sort_by: string | null;
    sort_direction: 'asc' | 'desc' | null;
} {
    const first = sorting[0];
    if (!first) {
        return { sort_by: null, sort_direction: null };
    }
    return {
        sort_by: first.id,
        sort_direction: first.desc ? 'desc' : 'asc',
    };
}

function sortingStatesEqual(a: SortingState, b: SortingState): boolean {
    if (a.length !== b.length) {
        return false;
    }
    for (let i = 0; i < a.length; i++) {
        if (a[i]?.id !== b[i]?.id || a[i]?.desc !== b[i]?.desc) {
            return false;
        }
    }
    return true;
}

export function DataTable({
    id,
    columns: schemaColumns,
    data: initialData,
    checkboxes = false,
    reorderable: reorderableProp,
    onRowClick,
    onValueChange,
    className,
    toolbarStart,
    toolbarAfterColumns,
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
    const [globalFilter, setGlobalFilter] = React.useState(serverSearch ?? '');
    const [serverFilterState, setServerFilterState] = React.useState<
        Record<string, string | string[] | null>
    >(() => normalizeServerFilterValues(serverFilterValues));
    const [sorting, setSorting] = React.useState<SortingState>([]);
    const [pagination, setPagination] = React.useState({
        pageIndex: 0,
        pageSize: 10,
    });

    const serverPaginationState = React.useMemo(() => {
        if (serverPagination == null) {
            return null;
        }
        return {
            pageIndex: serverPagination.current_page - 1,
            pageSize: serverPagination.per_page,
        };
    }, [serverPagination]);

    const paginationState = serverPaginationState ?? pagination;

    React.useEffect(() => {
        if (serverPagination == null) {
            return;
        }
        setGlobalFilter(serverSearch ?? '');
    }, [serverPagination, serverSearch]);

    React.useEffect(() => {
        if (serverPagination == null) {
            return;
        }
        setServerFilterState(normalizeServerFilterValues(serverFilterValues));
    }, [serverFilterValues, serverPagination]);

    React.useEffect(() => {
        if (serverPagination == null) {
            return;
        }
        const nextSorting: SortingState =
            serverSorting.sort_by == null
                ? []
                : [
                      {
                          id: serverSorting.sort_by,
                          desc: serverSorting.sort_direction === 'desc',
                      },
                  ];
        setSorting((prev) =>
            sortingStatesEqual(prev, nextSorting) ? prev : nextSorting,
        );
    }, [serverPagination, serverSorting.sort_by, serverSorting.sort_direction]);

    const handlePaginationChange = React.useCallback(
        (
            updater: React.SetStateAction<{
                pageIndex: number;
                pageSize: number;
            }>,
        ) => {
            if (serverPagination != null && onServerPaginationChange != null) {
                const next =
                    typeof updater === 'function'
                        ? updater(paginationState)
                        : updater;
                onServerPaginationChange(
                    next.pageIndex + 1,
                    next.pageSize,
                    globalFilter,
                    serverFilterState,
                    serverSortingFromState(sorting),
                );
                return;
            }
            setPagination(updater);
        },
        [
            globalFilter,
            onServerPaginationChange,
            paginationState,
            serverFilterState,
            serverPagination,
            sorting,
        ],
    );

    const handleSortingChange = React.useCallback(
        (updater: React.SetStateAction<SortingState>) => {
            if (serverPagination != null && onServerPaginationChange != null) {
                const nextSorting =
                    typeof updater === 'function' ? updater(sorting) : updater;
                setSorting(nextSorting);
                onServerPaginationChange(
                    1,
                    paginationState.pageSize,
                    globalFilter,
                    serverFilterState,
                    serverSortingFromState(nextSorting),
                );
                return;
            }
            setSorting(updater);
        },
        [
            globalFilter,
            onServerPaginationChange,
            paginationState.pageSize,
            serverFilterState,
            serverPagination,
            sorting,
        ],
    );

    React.useEffect(() => {
        if (serverPagination == null || onServerPaginationChange == null) {
            return;
        }
        const normalizedServerSearch = serverSearch ?? '';
        const normalizedServerFilters =
            normalizeServerFilterValues(serverFilterValues);
        if (
            globalFilter === normalizedServerSearch &&
            filterValuesEqual(serverFilterState, normalizedServerFilters)
        ) {
            return;
        }
        const debounce = window.setTimeout(() => {
            onServerPaginationChange(
                1,
                paginationState.pageSize,
                globalFilter,
                serverFilterState,
                serverSortingFromState(sorting),
            );
        }, 250);

        return () => window.clearTimeout(debounce);
    }, [
        globalFilter,
        onServerPaginationChange,
        paginationState.pageSize,
        serverFilterState,
        serverFilterValues,
        serverPagination,
        serverSearch,
        sorting,
    ]);

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
        getRowId: (row, index) => stableRowId(row, index),
        enableRowSelection: checkboxes,
        onRowSelectionChange: setRowSelection,
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
    const tableRows = table.getRowModel().rows;
    const hasRows = tableRows.length > 0;
    const paginationStateCurrent = table.getState().pagination;

    const rowCountLabel = checkboxes
        ? `${table.getFilteredSelectedRowModel().rows.length} of ${table.getFilteredRowModel().rows.length} row(s) selected.`
        : serverPagination
          ? serverPagination.total === 0
              ? '0 row(s).'
              : serverPagination.from != null && serverPagination.to != null
                ? `${serverPagination.from}–${serverPagination.to} of ${serverPagination.total} row(s).`
                : `${serverPagination.total} row(s).`
          : `${table.getFilteredRowModel().rows.length} row(s).`;

    const setSingleServerFilter = React.useCallback(
        (filterId: string, value: string) => {
            setServerFilterState((prev) => ({
                ...prev,
                [filterId]: value === '' ? null : value,
            }));
        },
        [],
    );

    const toggleMultiServerFilterValue = React.useCallback(
        (filterId: string, optionValue: string) => {
            setServerFilterState((prev) => {
                const current = prev[filterId];
                const currentValues = Array.isArray(current)
                    ? current
                    : current
                      ? [current]
                      : [];
                const exists = currentValues.includes(optionValue);
                const nextValues = exists
                    ? currentValues.filter((value) => value !== optionValue)
                    : [...currentValues, optionValue];
                return {
                    ...prev,
                    [filterId]: nextValues.length > 0 ? nextValues : null,
                };
            });
        },
        [],
    );

    const setDateServerFilter = React.useCallback(
        (filterId: string, date?: Date) => {
            setServerFilterState((prev) => ({
                ...prev,
                [filterId]: date ? format(date, 'yyyy-MM-dd') : null,
            }));
        },
        [],
    );

    const tableContent = (
        <Table>
            <TableHeader className="sticky top-0 z-10 bg-muted">
                {table.getHeaderGroups().map((headerGroup) => (
                    <TableRow key={headerGroup.id}>
                        {headerGroup.headers.map((header) => (
                            <TableHead
                                key={header.id}
                                colSpan={header.colSpan}
                                className={cn(
                                    header.column.id === 'drag' &&
                                        DATA_TABLE_DRAG_COLUMN_HEAD_CLASS,
                                )}
                            >
                                {header.isPlaceholder
                                    ? null
                                    : flexRender(
                                          header.column.columnDef.header,
                                          header.getContext(),
                                      )}
                            </TableHead>
                        ))}
                    </TableRow>
                ))}
            </TableHeader>
            <TableBody>
                {hasRows ? (
                    isReorderable ? (
                        <SortableContext
                            items={tableRows.map((row) => row.id)}
                            strategy={verticalListSortingStrategy}
                        >
                            {tableRows.map((row) => (
                                <DataTableDraggableRow
                                    key={row.id}
                                    row={row}
                                    onRowClick={handleRowClick}
                                />
                            ))}
                        </SortableContext>
                    ) : (
                        tableRows.map((row) => (
                            <TableRow
                                key={row.id}
                                data-state={row.getIsSelected() && 'selected'}
                                className={cn(onRowClick && 'cursor-pointer')}
                                onClick={(event) =>
                                    handleRowClick(event, row.original)
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
                    )
                ) : (
                    <TableRow>
                        <TableCell
                            colSpan={columnDefs.length}
                            className="h-24 text-center"
                        >
                            {DATA_TABLE_EMPTY_RESULTS_LABEL}
                        </TableCell>
                    </TableRow>
                )}
            </TableBody>
        </Table>
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
                        {tableContent}
                    </DndContext>
                ) : (
                    tableContent
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

            <div className="flex w-full items-start gap-3">
                <div className="min-w-0 flex-1 space-y-2">
                    {hasSearchableColumns ? (
                        <div className="relative w-full max-w-md">
                            <Label htmlFor={`${id}-search`} className="sr-only">
                                Search rows
                            </Label>
                            <SearchIcon
                                className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden
                            />
                            <Input
                                id={`${id}-search`}
                                type="search"
                                value={globalFilter}
                                onChange={(e) =>
                                    setGlobalFilter(e.target.value)
                                }
                                placeholder={DATA_TABLE_SEARCH_PLACEHOLDER}
                                className="h-8 pl-8"
                                autoComplete="off"
                            />
                        </div>
                    ) : null}
                </div>
                <div className="flex shrink-0 flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                    {serverFilters.length > 0 ? (
                        <div className="flex flex-wrap items-center gap-2">
                            {serverFilters.map((filter) => {
                                if (filter.type === 'select') {
                                    const selectedValue =
                                        serverFilterState[filter.id];
                                    const options = filter.options ?? [];
                                    if (filter.multiple) {
                                        const selected = Array.isArray(
                                            selectedValue,
                                        )
                                            ? selectedValue
                                            : selectedValue
                                              ? [selectedValue]
                                              : [];
                                        return (
                                            <DropdownMenu key={filter.id}>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        {filter.placeholder ??
                                                            filter.label}
                                                        {selected.length > 0
                                                            ? ` (${selected.length})`
                                                            : ''}
                                                        <ChevronDownIcon data-icon="inline-end" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent className="w-56">
                                                    {options.map((option) => (
                                                        <DropdownMenuCheckboxItem
                                                            key={`${filter.id}-${option.value}`}
                                                            checked={selected.includes(
                                                                option.value,
                                                            )}
                                                            onCheckedChange={() =>
                                                                toggleMultiServerFilterValue(
                                                                    filter.id,
                                                                    option.value,
                                                                )
                                                            }
                                                        >
                                                            {option.label}
                                                        </DropdownMenuCheckboxItem>
                                                    ))}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        );
                                    }
                                    const normalized =
                                        typeof selectedValue === 'string'
                                            ? selectedValue
                                            : '';
                                    return (
                                        <div
                                            key={filter.id}
                                            className="flex items-center gap-1"
                                        >
                                            <Select
                                                value={
                                                    normalized === ''
                                                        ? ALL_FILTER_OPTION_VALUE
                                                        : normalized
                                                }
                                                onValueChange={(value) =>
                                                    setSingleServerFilter(
                                                        filter.id,
                                                        value ===
                                                            ALL_FILTER_OPTION_VALUE
                                                            ? ''
                                                            : value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger
                                                    size="sm"
                                                    className="min-w-40"
                                                    id={`${id}-filter-${filter.id}`}
                                                >
                                                    <SelectValue
                                                        placeholder={
                                                            filter.placeholder ??
                                                            filter.label
                                                        }
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectGroup>
                                                        <SelectItem
                                                            value={
                                                                ALL_FILTER_OPTION_VALUE
                                                            }
                                                        >
                                                            All {filter.label}
                                                        </SelectItem>
                                                        {options.map(
                                                            (option) => (
                                                                <SelectItem
                                                                    key={`${filter.id}-${option.value}`}
                                                                    value={
                                                                        option.value
                                                                    }
                                                                >
                                                                    {
                                                                        option.label
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>
                                            {normalized !== '' ? (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                    onClick={() =>
                                                        setSingleServerFilter(
                                                            filter.id,
                                                            '',
                                                        )
                                                    }
                                                >
                                                    <span className="sr-only">
                                                        Clear {filter.label}
                                                    </span>
                                                    <XIcon className="size-4" />
                                                </Button>
                                            ) : null}
                                        </div>
                                    );
                                }

                                const dateValue =
                                    typeof serverFilterState[filter.id] ===
                                    'string'
                                        ? serverFilterState[filter.id]
                                        : '';
                                const selectedDate = dateValue
                                    ? new Date(`${dateValue}T00:00:00`)
                                    : undefined;
                                return (
                                    <div
                                        key={filter.id}
                                        className="flex items-center gap-1"
                                    >
                                        <Popover>
                                            <PopoverTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <CalendarIcon data-icon="inline-start" />
                                                    {selectedDate
                                                        ? format(
                                                              selectedDate,
                                                              'PPP',
                                                          )
                                                        : (filter.placeholder ??
                                                          filter.label)}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent
                                                className="w-auto p-0"
                                                align="start"
                                            >
                                                <Calendar
                                                    mode="single"
                                                    selected={selectedDate}
                                                    onSelect={(date) =>
                                                        setDateServerFilter(
                                                            filter.id,
                                                            date,
                                                        )
                                                    }
                                                />
                                            </PopoverContent>
                                        </Popover>
                                        {selectedDate ? (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                                onClick={() =>
                                                    setDateServerFilter(
                                                        filter.id,
                                                    )
                                                }
                                            >
                                                <span className="sr-only">
                                                    Clear {filter.label}
                                                </span>
                                                <XIcon className="size-4" />
                                            </Button>
                                        ) : null}
                                    </div>
                                );
                            })}
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
                                                'undefined' &&
                                            column.getCanHide(),
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
            </div>

            {tableAndFooter}
        </div>
    );
}
