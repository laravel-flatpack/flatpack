import { DatePickerField } from '@/components/form-fields';
import { DASHBOARD_TABLE_INPUT_CLASS } from '@/components/table/data-table-constants';
import {
    dateInputSegment,
    formatCellValue,
    mergeCommittedDate,
    readOnlyTruncatedDisplay,
} from '@/lib/data-table-utils';
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
        const selectedDate =
            day === '' ? undefined : new Date(`${day}T00:00:00`);

        return (
            <DatePickerField
                id={controlId}
                label={col.label}
                emptyLabel="Pick a date"
                value={selectedDate}
                inline
                triggerClassName={DASHBOARD_TABLE_INPUT_CLASS}
                onValueChange={(nextDate) => {
                    const nextDay =
                        nextDate == null
                            ? ''
                            : nextDate.toISOString().slice(0, 10);
                    commit(mergeCommittedDate(nextDay, value));
                }}
            />
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
