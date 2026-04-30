import type { ColumnDef } from '@tanstack/react-table';
import { ActionsCell } from '@/components/list-columns/actions-cell';
import { ColumnCellDispatcher } from '@/components/list-columns/column-cell-dispatcher';
import { DataTableColumnHeader } from '@/components/table/data-table-column-header';
import { DATA_TABLE_ROW_SELECTION_COLUMN_ID } from '@/components/table/data-table-constants';
import { Checkbox } from '@/components/ui/checkbox';
import type {
    BuildDataTableColumnDefsOptions,
    FlatpackDataTableColumn,
    FlatpackDataTableColumnMeta,
} from '@/types/data-table';

export function buildDataTableColumnDefs(
    schemaColumns: FlatpackDataTableColumn[],
    options: BuildDataTableColumnDefsOptions,
): ColumnDef<Record<string, unknown>>[] {
    const defs: ColumnDef<Record<string, unknown>>[] = [];

    if (options.reorderable) {
        defs.push({
            id: 'drag',
            header: () => <span className="sr-only">Reorder</span>,
            cell: () => null,
            enableSorting: false,
            enableHiding: false,
        });
    }

    if (options.hasBulkActions) {
        defs.push({
            id: DATA_TABLE_ROW_SELECTION_COLUMN_ID,
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
                <div className="flex items-center justify-start">
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
        const columnSortable = col.type !== 'actions' && col.sortable === true;
        defs.push({
            id: col.id,
            accessorKey: col.id,
            meta: {
                label: col.label,
            } satisfies FlatpackDataTableColumnMeta,
            header: columnSortable
                ? ({ column }) => (
                      <DataTableColumnHeader
                          column={column}
                          label={col.label}
                      />
                  )
                : col.label,
            enableSorting: columnSortable,
            enableHiding: true,
            cell: ({ row }) => {
                if (col.type === 'actions') {
                    const rowActions = Array.isArray(col.actions)
                        ? col.actions
                        : [];
                    if (rowActions.length === 0) {
                        return <span className="text-muted-foreground">—</span>;
                    }
                    return (
                        <ActionsCell
                            actions={rowActions}
                            row={row.original}
                            onAction={options.onRowAction}
                            requireRowId={options.requireRowIdForActions}
                        />
                    );
                }
                return (
                    <ColumnCellDispatcher
                        column={col}
                        row={row.original}
                        rowId={row.id}
                        schemaColumns={schemaColumns}
                        value={row.getValue(col.id)}
                        onCellChange={options.onCellChange}
                        onRowReplace={options.onRowReplace}
                        inlineCellEdit={options.inlineCellEdit !== false}
                    />
                );
            },
        });
    }

    return defs;
}
