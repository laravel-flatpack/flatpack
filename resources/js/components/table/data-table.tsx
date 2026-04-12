import { Link } from '@inertiajs/react';
import {
    type ColumnDef,
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
    type LucideIcon,
    PencilIcon,
    Trash2Icon,
} from 'lucide-react';
import * as React from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { cn } from '@/lib/utils';
import type {
    FlatpackDataTableActionButton,
    FlatpackDataTableColumn,
} from '@/types/data-table';

function interpolateRowPlaceholders(
    template: string,
    row: Record<string, unknown>,
): string {
    return template.replace(/\{([^}]+)\}/g, (_, rawKey: string) => {
        const key = rawKey.trim();
        const v = row[key];
        if (v == null) {
            return '';
        }
        return String(v);
    });
}

function iconForAction(iconOrKey?: string): LucideIcon | null {
    const k = iconOrKey?.toLowerCase() ?? '';
    if (k === 'edit' || k === 'pencil') {
        return PencilIcon;
    }
    if (k === 'delete' || k === 'trash' || k === 'remove') {
        return Trash2Icon;
    }
    return null;
}

function ActionsCell({
    buttons,
    row,
}: {
    buttons: Record<string, FlatpackDataTableActionButton>;
    row: Record<string, unknown>;
}) {
    return (
        <div className="flex flex-row flex-nowrap items-center justify-end gap-1 whitespace-nowrap">
            {Object.entries(buttons).map(([actionKey, cfg]) => {
                const template = cfg.href ?? cfg.url ?? '';
                const resolved = template
                    ? interpolateRowPlaceholders(template, row)
                    : '';
                const Icon =
                    iconForAction(cfg.icon) ?? iconForAction(actionKey);
                const destructive =
                    actionKey.toLowerCase() === 'delete' ||
                    cfg.icon?.toLowerCase() === 'delete';
                const label = (
                    <>
                        {Icon ? (
                            <Icon className="size-3.5 shrink-0" aria-hidden />
                        ) : null}
                        <span>{cfg.label}</span>
                    </>
                );

                if (resolved) {
                    const external = /^https?:\/\//i.test(resolved);
                    return (
                        <Button
                            key={actionKey}
                            variant="ghost"
                            size="sm"
                            className={cn(
                                'h-8 gap-1 px-2 has-[>svg]:px-2',
                                destructive &&
                                    'text-destructive hover:bg-destructive/10 hover:text-destructive',
                            )}
                            asChild
                        >
                            {external ? (
                                <a
                                    href={resolved}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {label}
                                </a>
                            ) : (
                                <Link href={resolved}>{label}</Link>
                            )}
                        </Button>
                    );
                }

                return (
                    <Button
                        key={actionKey}
                        type="button"
                        variant="ghost"
                        size="sm"
                        className={cn(
                            'h-8 gap-1 px-2',
                            destructive &&
                                'text-destructive hover:bg-destructive/10 hover:text-destructive',
                        )}
                    >
                        {label}
                    </Button>
                );
            })}
        </div>
    );
}

function formatCellValue(raw: unknown): string {
    if (raw == null) {
        return '';
    }
    if (typeof raw === 'object') {
        return JSON.stringify(raw);
    }
    return String(raw);
}

function badgeClassForOptionColor(color?: string): string {
    switch (color) {
        case 'green':
            return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300';
        case 'red':
            return 'border-red-500/30 bg-red-500/10 text-red-800 dark:text-red-300';
        case 'yellow':
            return 'border-amber-500/30 bg-amber-500/10 text-amber-900 dark:text-amber-200';
        default:
            return '';
    }
}

function SchemaTableCell({
    column: col,
    value,
}: {
    column: FlatpackDataTableColumn;
    value: unknown;
}) {
    if (col.type === 'select' && col.options?.length) {
        const str = value == null ? '' : String(value);
        const opt = col.options.find((o) => o.value === str);
        const display = opt?.label ?? str;
        const colorClass = badgeClassForOptionColor(opt?.color);
        return (
            <Badge
                variant="outline"
                className={cn('max-w-full px-1.5 font-normal', colorClass)}
            >
                <span className="truncate">{display}</span>
            </Badge>
        );
    }

    if (col.type === 'date') {
        const s = formatCellValue(value);
        if (!s) {
            return <span className="text-muted-foreground">—</span>;
        }
        const datePart = s.includes(' ') ? s.slice(0, 10) : s.slice(0, 10);
        return (
            <span className="tabular-nums text-muted-foreground">
                {datePart}
            </span>
        );
    }

    return (
        <span className="block min-w-0 truncate" title={formatCellValue(value)}>
            {formatCellValue(value)}
        </span>
    );
}

export function buildDataTableColumnDefs(
    schemaColumns: FlatpackDataTableColumn[],
    options: { checkboxes?: boolean },
): ColumnDef<Record<string, unknown>>[] {
    const defs: ColumnDef<Record<string, unknown>>[] = [];

    if (options.checkboxes) {
        defs.push({
            id: 'select',
            header: ({ table }) => (
                <div className="flex items-center justify-start">
                    <Checkbox
                        checked={
                            table.getIsAllPageRowsSelected() ||
                            (table.getIsSomePageRowsSelected() &&
                                'indeterminate')
                        }
                        onCheckedChange={(value) =>
                            table.toggleAllPageRowsSelected(!!value)
                        }
                        aria-label="Select all"
                    />
                </div>
            ),
            cell: ({ row }) => (
                <div className="flex items-center justify-center">
                    <Checkbox
                        checked={row.getIsSelected()}
                        onCheckedChange={(value) => row.toggleSelected(!!value)}
                        aria-label="Select row"
                    />
                </div>
            ),
            enableSorting: false,
            enableHiding: false,
        });
    }

    for (const col of schemaColumns) {
        defs.push({
            id: col.id,
            accessorKey: col.id,
            header: col.label,
            enableSorting:
                col.type === 'actions' ? false : col.sortable !== false,
            enableHiding: true,
            cell: ({ row }) => {
                if (col.type === 'actions') {
                    if (!col.buttons || Object.keys(col.buttons).length === 0) {
                        return <span className="text-muted-foreground">—</span>;
                    }
                    return (
                        <ActionsCell buttons={col.buttons} row={row.original} />
                    );
                }
                return (
                    <SchemaTableCell
                        column={col}
                        value={row.getValue(col.id)}
                    />
                );
            },
        });
    }

    return defs;
}

function visibilityFromSchema(
    columns: FlatpackDataTableColumn[],
): VisibilityState {
    const state: VisibilityState = {};
    for (const col of columns) {
        if (col.invisible) {
            state[col.id] = false;
        }
    }
    return state;
}

/** Full leaf column id list in schema order (incl. hidden). Keeps TanStack from appending shown columns at the end. */
function leafColumnIdsInSchemaOrder(
    schemaColumns: FlatpackDataTableColumn[],
    checkboxes: boolean,
): string[] {
    return [
        ...(checkboxes ? ['select'] : []),
        ...schemaColumns.map((c) => c.id),
    ];
}

export type DataTableProps = {
    /** Field id for accessibility (from form binding context). */
    id: string;
    columns: FlatpackDataTableColumn[];
    data: Record<string, unknown>[];
    checkboxes?: boolean;
    className?: string;
};

/**
 * TanStack table driven by a Flatpack column schema and row `data` (plain objects).
 */
export function DataTable({
    id,
    columns: schemaColumns,
    data: initialData,
    checkboxes = false,
    className,
}: DataTableProps) {
    const data = React.useMemo(() => initialData, [initialData]);
    const [rowSelection, setRowSelection] = React.useState({});
    const [columnVisibility, setColumnVisibility] =
        React.useState<VisibilityState>(() =>
            visibilityFromSchema(schemaColumns),
        );
    const schemaLeafOrder = React.useMemo(
        () => leafColumnIdsInSchemaOrder(schemaColumns, checkboxes),
        [schemaColumns, checkboxes],
    );
    const [columnOrder, setColumnOrder] = React.useState<string[]>(() =>
        leafColumnIdsInSchemaOrder(schemaColumns, checkboxes),
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

    const columnDefs = React.useMemo(
        () =>
            buildDataTableColumnDefs(schemaColumns, {
                checkboxes,
            }),
        [schemaColumns, checkboxes],
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
            pagination,
        },
        getRowId: (row, index) => {
            const idVal = row.id;
            if (idVal !== undefined && idVal !== null) {
                return String(idVal);
            }
            return `row-${index}`;
        },
        enableRowSelection: checkboxes,
        onRowSelectionChange: setRowSelection,
        onSortingChange: setSorting,
        onColumnFiltersChange: setColumnFilters,
        onColumnVisibilityChange: setColumnVisibility,
        onColumnOrderChange: setColumnOrder,
        onPaginationChange: setPagination,
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getFacetedRowModel: getFacetedRowModel(),
        getFacetedUniqueValues: getFacetedUniqueValues(),
    });

    const tableLabelId = `${id}-table-label`;

    return (
        <div
            className={cn('flex w-full flex-col gap-4', className)}
            role="region"
            aria-labelledby={tableLabelId}
        >
            <span id={tableLabelId} className="sr-only">
                Data table
            </span>
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
                                    typeof column.accessorFn !== 'undefined' &&
                                    column.getCanHide(),
                            )
                            .map((column) => (
                                <DropdownMenuCheckboxItem
                                    key={column.id}
                                    className="capitalize"
                                    checked={column.getIsVisible()}
                                    onCheckedChange={(value) =>
                                        column.toggleVisibility(!!value)
                                    }
                                >
                                    {typeof column.columnDef.header === 'string'
                                        ? column.columnDef.header
                                        : column.id}
                                </DropdownMenuCheckboxItem>
                            ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <div className="overflow-hidden rounded-lg border">
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
        </div>
    );
}
