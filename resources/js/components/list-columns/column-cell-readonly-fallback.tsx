import {
    formatCellValue,
    readOnlyTruncatedDisplay,
} from '@/lib/data-table-utils';
import type { ListColumnRenderProps } from '@/types/list-columns';

export function ReadOnlyFallbackColumnCell({
    column,
    value,
}: Pick<ListColumnRenderProps, 'column' | 'value'>) {
    const full = formatCellValue(value);
    const { shown, title } = readOnlyTruncatedDisplay(full, column.truncate);
    return (
        <span className="block min-w-0" title={title}>
            {shown}
        </span>
    );
}
