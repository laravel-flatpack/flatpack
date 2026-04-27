import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { flexRender, type Row } from '@tanstack/react-table';
import { AlertCircleIcon, GripVerticalIcon } from 'lucide-react';
import {
    DATA_TABLE_DRAG_COLUMN_CELL_CLASS,
    DATA_TABLE_SELECT_COLUMN_CELL_CLASS,
} from '@/components/table/data-table-constants';
import { Button } from '@/components/ui/button';
import { TableCell, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';

export function DataTableDraggableRow({
    row,
    onRowClick,
    validationMessages = [],
}: {
    row: Row<Record<string, unknown>>;
    onRowClick?: (
        event: React.MouseEvent<HTMLTableRowElement>,
        row: Record<string, unknown>,
    ) => void;
    validationMessages?: string[];
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: row.id });

    return (
        <TableRow
            ref={setNodeRef}
            data-state={row.getIsSelected() && 'selected'}
            data-dragging={isDragging}
            className={cn(
                'relative z-0 data-[dragging=true]:z-10 data-[dragging=true]:opacity-80',
                onRowClick && 'cursor-pointer',
                validationMessages.length > 0 && 'bg-destructive/5',
            )}
            style={{
                transform: CSS.Transform.toString(transform),
                transition,
            }}
            onClick={
                onRowClick
                    ? (event) => {
                          onRowClick(event, row.original);
                      }
                    : undefined
            }
        >
            {row.getVisibleCells().map((cell, cellIndex) => (
                <TableCell
                    key={cell.id}
                    className={cn(
                        cell.column.id === 'drag' &&
                            DATA_TABLE_DRAG_COLUMN_CELL_CLASS,
                        cell.column.id === 'select' &&
                            DATA_TABLE_SELECT_COLUMN_CELL_CLASS,
                    )}
                >
                    <div className="flex items-center gap-2">
                        {cell.column.id === 'drag' ? (
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-xs"
                                className="cursor-grab text-muted-foreground hover:bg-transparent active:cursor-grabbing"
                                {...attributes}
                                {...listeners}
                            >
                                <GripVerticalIcon
                                    className="size-3 shrink-0"
                                    aria-hidden
                                />
                                <span className="sr-only">
                                    Drag to reorder row
                                </span>
                            </Button>
                        ) : (
                            flexRender(
                                cell.column.columnDef.cell,
                                cell.getContext(),
                            )
                        )}
                        {cellIndex === 0 && validationMessages.length > 0 ? (
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
}
