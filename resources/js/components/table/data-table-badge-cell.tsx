import { Badge } from '@/components/ui/badge';
import { formatCellValue, readOnlyTruncatedDisplay } from '@/lib/data-table-utils';

export function DataTableBadgeCell({
    value,
    truncate,
}: {
    value: unknown;
    truncate?: number;
}) {
    const text = formatCellValue(value);
    const full = text || '—';
    const { shown, title } = readOnlyTruncatedDisplay(full, truncate);
    return (
        <div className="w-32 min-w-0 shrink-0">
            <Badge
                variant="outline"
                className="max-w-full px-1.5 font-normal text-muted-foreground"
            >
                <span className="block min-w-0" title={title}>
                    {shown}
                </span>
            </Badge>
        </div>
    );
}
