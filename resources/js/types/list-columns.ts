import type { FlatpackDataTableColumn } from '@/types/data-table';

export type ListColumnRenderProps = {
    column: FlatpackDataTableColumn;
    value: unknown;
    rowId: string;
    row: Record<string, unknown>;
    schemaColumns: FlatpackDataTableColumn[];
    onCellChange?: (rowId: string, columnId: string, next: unknown) => void;
    onRowReplace?: (rowId: string, nextRow: Record<string, unknown>) => void;
    inlineCellEdit?: boolean;
};

export type ListColumnSharedContext = {
    editable: boolean;
    controlId: string;
    commit: (next: unknown) => void;
};
