import * as React from 'react';
import { TextField } from '@/components/form-fields';
import { DASHBOARD_TABLE_INPUT_CLASS } from '@/components/table/data-table-constants';
import { formatCellValue } from '@/lib/data-table-utils';

export function EditableTextCell({
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
        <TextField
            id={controlId}
            label={ariaLabel}
            placeholder=""
            value={draft}
            inline
            inputClassName={DASHBOARD_TABLE_INPUT_CLASS}
            onValueChange={setDraft}
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
    );
}
