import { Badge } from '@/components/ui/badge';
import {
    formatCellValue,
    readOnlyTruncatedDisplay,
} from '@/lib/data-table-utils';
import { selectOptionLeadingIcon } from '@/lib/select-option-leading-icon';
import { cn } from '@/lib/utils';
import type {
    FlatpackDataTableColumn,
    FlatpackDataTableSelectOptionStatus,
} from '@/types/data-table';

const BADGE_STATUS_CLASS: Record<FlatpackDataTableSelectOptionStatus, string> = {
    success:
        'border-green-500/30 bg-green-500/10 text-green-700 dark:border-green-400/30 dark:bg-green-400/10 dark:text-green-300',
    pending:
        'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:border-sky-400/30 dark:bg-sky-400/10 dark:text-sky-300',
    warning:
        'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-300',
    error: 'border-red-500/30 bg-red-500/10 text-red-700 dark:border-red-400/30 dark:bg-red-400/10 dark:text-red-300',
    info: 'border-blue-500/30 bg-blue-500/10 text-blue-700 dark:border-blue-400/30 dark:bg-blue-400/10 dark:text-blue-300',
};

export function DataTableBadgeCell({
    column,
    value,
    truncate,
}: {
    column: FlatpackDataTableColumn;
    value: unknown;
    truncate?: number;
}) {
    const options = column.options ?? [];
    const valueAsString = value == null ? '' : String(value);
    const selectedOption = options.find((option) => option.value === valueAsString);
    const text = selectedOption?.label ?? formatCellValue(value);
    const full = text || '—';
    const { shown, title } = readOnlyTruncatedDisplay(full, truncate);

    return (
        <div className="w-32 min-w-0 shrink-0">
            <Badge
                variant="outline"
                className={cn(
                    'inline-flex max-w-full items-center gap-1.5 px-1.5 font-normal',
                    selectedOption?.status == null
                        ? 'text-muted-foreground'
                        : BADGE_STATUS_CLASS[selectedOption.status],
                )}
            >
                {selectOptionLeadingIcon(selectedOption)}
                <span className="block min-w-0 truncate" title={title}>
                    {shown}
                </span>
            </Badge>
        </div>
    );
}
