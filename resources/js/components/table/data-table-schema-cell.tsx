import { DataTableBadgeCell } from '@/components/table/data-table-badge-cell';
import { DataTableDateCell } from '@/components/table/data-table-date-cell';
import { DataTableRowDetailDrawer } from '@/components/table/data-table-row-drawer';
import { DataTableSelectCell } from '@/components/table/data-table-select-cell';
import { DataTableEditableTextCell } from '@/components/table/data-table-text-cell';
import {
    cellControlDomId,
    formatCellValue,
    readOnlyTruncatedDisplay,
} from '@/lib/data-table-utils';
import type { FlatpackDataTableColumn } from '@/types/data-table';

export function DataTableSchemaCell({
    column: col,
    value,
    rowId,
    row,
    schemaColumns,
    onCellChange,
    onRowReplace,
}: {
    column: FlatpackDataTableColumn;
    value: unknown;
    rowId: string;
    row: Record<string, unknown>;
    schemaColumns: FlatpackDataTableColumn[];
    onCellChange?: (rowId: string, columnId: string, next: unknown) => void;
    onRowReplace?: (rowId: string, nextRow: Record<string, unknown>) => void;
}) {
    if (col.detailDrawer === true && onRowReplace) {
        return (
            <DataTableRowDetailDrawer
                onRowReplace={onRowReplace}
                row={row}
                rowId={rowId}
                schemaColumns={schemaColumns}
                triggerColumn={col}
            />
        );
    }

    const editable =
        col.editable === true &&
        col.type !== 'actions' &&
        onCellChange != null &&
        col.detailDrawer !== true;
    const commit = (next: unknown) => onCellChange?.(rowId, col.id, next);
    const controlId = cellControlDomId(rowId, col.id);

    if (col.type === 'badge') {
        return (
            <DataTableBadgeCell value={value} truncate={col.truncate} />
        );
    }

    if (col.type === 'select' && col.options?.length) {
        return (
            <DataTableSelectCell
                column={col}
                value={value}
                editable={editable}
                controlId={controlId}
                commit={commit}
            />
        );
    }

    if (col.type === 'date') {
        return (
            <DataTableDateCell
                column={col}
                value={value}
                editable={editable}
                controlId={controlId}
                commit={commit}
            />
        );
    }

    if (editable) {
        return (
            <DataTableEditableTextCell
                value={value}
                commit={commit}
                ariaLabel={col.label}
                controlId={controlId}
            />
        );
    }

    const full = formatCellValue(value);
    const { shown, title } = readOnlyTruncatedDisplay(full, col.truncate);
    return (
        <span className="block min-w-0" title={title}>
            {shown}
        </span>
    );
}
