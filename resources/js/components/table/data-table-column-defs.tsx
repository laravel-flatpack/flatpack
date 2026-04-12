import type { ColumnDef } from '@tanstack/react-table';
import { DataTableActionsCell } from '@/components/table/data-table-actions-cell';
import { DataTableColumnHeader } from '@/components/table/data-table-column-header';
import { DataTableSchemaCell } from '@/components/table/data-table-schema-cell';
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
                    if (!col.buttons || Object.keys(col.buttons).length === 0) {
                        return <span className="text-muted-foreground">—</span>;
                    }
                    return (
                        <DataTableActionsCell
                            buttons={col.buttons}
                            row={row.original}
                        />
                    );
                }
                return (
                    <DataTableSchemaCell
                        column={col}
                        row={row.original}
                        rowId={row.id}
                        schemaColumns={schemaColumns}
                        value={row.getValue(col.id)}
                        onCellChange={options.onCellChange}
                        onRowReplace={options.onRowReplace}
                    />
                );
            },
        });
    }

    return defs;
}
