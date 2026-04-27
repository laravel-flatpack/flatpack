import {
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { flexRender, type Table as TanStackTable } from '@tanstack/react-table';
import { AlertCircleIcon } from 'lucide-react';
import type * as React from 'react';
import {
    DATA_TABLE_DRAG_COLUMN_HEAD_CLASS,
    DATA_TABLE_EMPTY_RESULTS_LABEL,
    DATA_TABLE_SELECT_COLUMN_CELL_CLASS,
    DATA_TABLE_SELECT_COLUMN_HEAD_CLASS,
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
import type { DataTableRowValidationMessagesById } from '@/types/data-table';

type DataTableBodyProps = {
    table: TanStackTable<Record<string, unknown>>;
    isReorderable: boolean;
    onRowClick?: (
        event: React.MouseEvent<HTMLTableRowElement>,
        row: Record<string, unknown>,
    ) => void;
    emptyColSpan: number;
    rowValidationMessagesById: DataTableRowValidationMessagesById;
};

export function DataTableBody({
    table,
    isReorderable,
    onRowClick,
    emptyColSpan,
    rowValidationMessagesById,
}: DataTableBodyProps) {
    const tableRows = table.getRowModel().rows;
    const hasRows = tableRows.length > 0;
    const hasRowClick = onRowClick != null;

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
                                    header.column.id === 'select' &&
                                        DATA_TABLE_SELECT_COLUMN_HEAD_CLASS,
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
                                    onRowClick={onRowClick}
                                    validationMessages={
                                        rowValidationMessagesById[row.id] ?? []
                                    }
                                />
                            ))}
                        </SortableContext>
                    ) : (
                        tableRows.map((row) => {
                            const rowMessages =
                                rowValidationMessagesById[row.id] ?? [];
                            const visibleCells = row.getVisibleCells();
                            return (
                                <TableRow
                                    key={row.id}
                                    data-state={
                                        row.getIsSelected() && 'selected'
                                    }
                                    className={cn(
                                        hasRowClick && 'cursor-pointer',
                                        rowMessages.length > 0 &&
                                            'bg-destructive/5',
                                    )}
                                    onClick={
                                        onRowClick
                                            ? (event) =>
                                                  onRowClick(
                                                      event,
                                                      row.original,
                                                  )
                                            : undefined
                                    }
                                >
                                    {visibleCells.map((cell, cellIndex) => (
                                        <TableCell
                                            key={cell.id}
                                            className={cn(
                                                cell.column.id === 'select' &&
                                                    DATA_TABLE_SELECT_COLUMN_CELL_CLASS,
                                            )}
                                        >
                                            <div className="flex items-center gap-2">
                                                {flexRender(
                                                    cell.column.columnDef.cell,
                                                    cell.getContext(),
                                                )}
                                                {cellIndex === 0 &&
                                                rowMessages.length > 0 ? (
                                                    <AlertCircleIcon
                                                        className="size-4 shrink-0 text-destructive"
                                                        aria-label="Validation errors"
                                                    />
                                                ) : null}
                                            </div>
                                        </TableCell>
                                    ))}
                                </TableRow>
                            );
                        })
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
