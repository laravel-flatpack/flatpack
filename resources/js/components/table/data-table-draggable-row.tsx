import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { flexRender, type Row } from '@tanstack/react-table';
import { GripVerticalIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { TableCell, TableRow } from '@/components/ui/table';

export function DataTableDraggableRow({
    row,
}: {
    row: Row<Record<string, unknown>>;
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
            className="relative z-0 data-[dragging=true]:z-10 data-[dragging=true]:opacity-80"
            style={{
                transform: CSS.Transform.toString(transform),
                transition,
            }}
        >
            {row.getVisibleCells().map((cell) => (
                <TableCell key={cell.id}>
                    {cell.column.id === 'drag' ? (
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-7 cursor-grab text-muted-foreground hover:bg-transparent active:cursor-grabbing"
                            {...attributes}
                            {...listeners}
                        >
                            <GripVerticalIcon
                                className="size-3.5 shrink-0"
                                aria-hidden
                            />
                            <span className="sr-only">Drag to reorder row</span>
                        </Button>
                    ) : (
                        flexRender(
                            cell.column.columnDef.cell,
                            cell.getContext(),
                        )
                    )}
                </TableCell>
            ))}
        </TableRow>
    );
}
