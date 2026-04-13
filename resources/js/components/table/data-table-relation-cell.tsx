import {
    formatRelationCellDisplay,
    readOnlyTruncatedDisplay,
} from '@/lib/data-table-utils';
import type { FlatpackDataTableColumn } from '@/types/data-table';

export function DataTableRelationCell({
    row,
    column: col,
}: {
    row: Record<string, unknown>;
    column: FlatpackDataTableColumn;
}) {
    const full = formatRelationCellDisplay(row, col);
    const { shown, title } = readOnlyTruncatedDisplay(full, col.truncate);
    if (full === '') {
        return <span className="text-muted-foreground">—</span>;
    }
    return (
        <span className="block min-w-0" title={title}>
            {shown}
        </span>
    );
}
