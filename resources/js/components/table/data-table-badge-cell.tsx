import { Badge } from '@/components/ui/badge';
import { formatCellValue } from '@/lib/data-table-utils';

export function DataTableBadgeCell({ value }: { value: unknown }) {
    const text = formatCellValue(value);
    return (
        <div className="w-32 min-w-0 shrink-0">
            <Badge
                variant="outline"
                className="max-w-full px-1.5 font-normal text-muted-foreground"
            >
                <span className="truncate">{text || '—'}</span>
            </Badge>
        </div>
    );
}
