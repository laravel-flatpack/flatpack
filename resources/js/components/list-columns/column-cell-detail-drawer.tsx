import { DataTableRowDetailDrawer } from '@/components/table/data-table-row-drawer';
import type { ListColumnRenderProps } from '@/types/list-columns';

export function DetailDrawerColumnCell({
    column,
    row,
    rowId,
    schemaColumns,
    onRowReplace,
}: ListColumnRenderProps) {
    if (column.detailDrawer !== true || onRowReplace == null) {
        return null;
    }

    return (
        <DataTableRowDetailDrawer
            onRowReplace={onRowReplace}
            row={row}
            rowId={rowId}
            schemaColumns={schemaColumns}
            triggerColumn={column}
        />
    );
}
