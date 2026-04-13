import { DASHBOARD_TABLE_INPUT_CLASS } from '@/components/table/data-table-constants';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    dateInputSegment,
    formatCellValue,
    mergeCommittedDate,
    readOnlyTruncatedDisplay,
} from '@/lib/data-table-utils';
import { cn } from '@/lib/utils';
import type { FlatpackDataTableColumn } from '@/types/data-table';

export function DataTableDateCell({
    column: col,
    value,
    editable,
    controlId,
    commit,
}: {
    column: FlatpackDataTableColumn;
    value: unknown;
    editable: boolean;
    controlId: string;
    commit: (next: unknown) => void;
}) {
    if (editable) {
        const day = dateInputSegment(value);
        return (
            <>
                <Label htmlFor={controlId} className="sr-only">
                    {col.label}
                </Label>
                <Input
                    id={controlId}
                    type="date"
                    className={cn(DASHBOARD_TABLE_INPUT_CLASS, 'tabular-nums')}
                    value={day}
                    onChange={(e) =>
                        commit(mergeCommittedDate(e.target.value, value))
                    }
                />
            </>
        );
    }

    const s = formatCellValue(value);
    if (!s) {
        return <span className="text-muted-foreground">—</span>;
    }
    const datePart = s.slice(0, 10);
    const { shown, title: truncateTitle } = readOnlyTruncatedDisplay(
        datePart,
        col.truncate,
    );
    const title =
        truncateTitle != null ? s : s.length > datePart.length ? s : undefined;
    return (
        <span className="tabular-nums text-muted-foreground" title={title}>
            {shown}
        </span>
    );
}
