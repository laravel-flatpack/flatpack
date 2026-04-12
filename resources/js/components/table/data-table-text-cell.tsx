import * as React from 'react';
import { DASHBOARD_TABLE_INPUT_CLASS } from '@/components/table/data-table-constants';
import { formatCellValue } from '@/components/table/data-table-utils';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function DataTableEditableTextCell({
    value,
    commit,
    ariaLabel,
    controlId,
}: {
    value: unknown;
    commit: (next: unknown) => void;
    ariaLabel: string;
    controlId: string;
}) {
    const [draft, setDraft] = React.useState(() => formatCellValue(value));
    React.useLayoutEffect(() => {
        setDraft(formatCellValue(value));
    }, [value]);

    return (
        <>
            <Label htmlFor={controlId} className="sr-only">
                {ariaLabel}
            </Label>
            <Input
                id={controlId}
                className={DASHBOARD_TABLE_INPUT_CLASS}
                value={draft}
                onChange={(e) => setDraft(e.target.value)}
                onBlur={() => {
                    if (draft !== formatCellValue(value)) {
                        commit(draft);
                    }
                }}
                onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                        (e.target as HTMLInputElement).blur();
                    }
                    if (e.key === 'Escape') {
                        setDraft(formatCellValue(value));
                        (e.target as HTMLInputElement).blur();
                    }
                }}
            />
        </>
    );
}
