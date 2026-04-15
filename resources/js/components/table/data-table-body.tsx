import {
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { flexRender, type Table as TanStackTable } from '@tanstack/react-table';
import type * as React from 'react';
import {
    DATA_TABLE_DRAG_COLUMN_HEAD_CLASS,
    DATA_TABLE_EMPTY_RESULTS_LABEL,
} from '@/components/table/data-table-constants';
import { DataTableDraggableRow } from '@/components/table/data-table-draggable-row';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

type DataTableBodyProps = {
    table: TanStackTable<Record<string, unknown>>;
    isReorderable: boolean;
    hasRowClick: boolean;
    onRowClick: (
        event: React.MouseEvent<HTMLTableRowElement>,
        row: Record<string, unknown>,
    ) => void;
    emptyColSpan: number;
};

export function DataTableBody({
    table,
    isReorderable,
    hasRowClick,
    onRowClick,
    emptyColSpan,
}: DataTableBodyProps) {
    const tableRows = table.getRowModel().rows;
    const hasRows = tableRows.length > 0;

    return (
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
                                    onRowClick={
                                        hasRowClick ? onRowClick : undefined
                                    }
                                />
                            ))}
                        </SortableContext>
                    ) : (
                        tableRows.map((row) => (
                            <TableRow
                                key={row.id}
                                data-state={row.getIsSelected() && 'selected'}
                                className={cn(hasRowClick && 'cursor-pointer')}
                                onClick={(event) =>
                                    onRowClick(event, row.original)
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
                            colSpan={emptyColSpan}
                            className="h-24 text-center"
                        >
                            {DATA_TABLE_EMPTY_RESULTS_LABEL}
                        </TableCell>
                    </TableRow>
                )}
            </TableBody>
        </Table>
    );
}
