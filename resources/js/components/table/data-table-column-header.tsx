import type { Column } from '@tanstack/react-table';
import { ArrowDownIcon, ArrowUpDownIcon, ArrowUpIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export function DataTableColumnHeader({
    column,
    label,
}: {
    column: Column<Record<string, unknown>, unknown>;
    label: string;
}) {
    const sorted = column.getIsSorted();
    return (
        <Button
            type="button"
            variant="ghost"
            className={cn('-ml-2 h-8 gap-1 px-2 font-medium has-[>svg]:px-2')}
            aria-sort={
                sorted === 'asc'
                    ? 'ascending'
                    : sorted === 'desc'
                      ? 'descending'
                      : 'none'
            }
            onClick={column.getToggleSortingHandler()}
        >
            {label}
            {sorted === 'desc' ? (
                <ArrowDownIcon
                    className="size-3.5 shrink-0 opacity-80"
                    aria-hidden
                />
            ) : sorted === 'asc' ? (
                <ArrowUpIcon
                    className="size-3.5 shrink-0 opacity-80"
                    aria-hidden
                />
            ) : (
                <ArrowUpDownIcon
                    className="size-3.5 shrink-0 opacity-45"
                    aria-hidden
                />
            )}
        </Button>
    );
}
