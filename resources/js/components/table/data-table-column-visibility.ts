import type { Column, VisibilityState } from '@tanstack/react-table';
import type {
    FlatpackDataTableColumn,
    FlatpackDataTableColumnMeta,
} from '@/types/data-table';

export function columnVisibilityMenuLabel(
    column: Column<Record<string, unknown>, unknown>,
): string {
    const meta = column.columnDef.meta as
        | FlatpackDataTableColumnMeta
        | undefined;
    if (meta?.label) {
        return meta.label;
    }
    const header = column.columnDef.header;
    return typeof header === 'string' ? header : column.id;
}

export function visibilityFromSchema(
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

export function leafColumnIdsInSchemaOrder(
    schemaColumns: FlatpackDataTableColumn[],
    hasBulkActions: boolean,
    reorderable: boolean,
): string[] {
    return [
        ...(reorderable ? ['drag'] : []),
        ...(hasBulkActions ? ['select'] : []),
        ...schemaColumns.map((c) => c.id),
    ];
}
