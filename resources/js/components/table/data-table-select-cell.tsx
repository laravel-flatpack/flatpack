import { DASHBOARD_TABLE_SELECT_TRIGGER_CLASS } from '@/components/table/data-table-constants';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { selectOptionLeadingIcon } from '@/lib/select-option-leading-icon';
import type { FlatpackDataTableColumn } from '@/types/data-table';

export function DataTableSelectCell({
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
    const options = col.options ?? [];
    if (!options.length) {
        return <span className="text-muted-foreground">—</span>;
    }

    const str = value == null ? '' : String(value);
    if (editable) {
        const validOption = options.some((o) => o.value === str);
        return (
            <>
                <Label htmlFor={controlId} className="sr-only">
                    {col.label}
                </Label>
                <Select
                    value={str !== '' && validOption ? str : undefined}
                    onValueChange={(v) => commit(v)}
                >
                    <SelectTrigger
                        id={controlId}
                        size="sm"
                        className={DASHBOARD_TABLE_SELECT_TRIGGER_CLASS}
                    >
                        <SelectValue placeholder="Choose…" />
                    </SelectTrigger>
                    <SelectContent align="end">
                        <SelectGroup>
                            {options.map((o) => (
                                <SelectItem key={o.value} value={o.value}>
                                    <span className="flex items-center gap-2">
                                        {selectOptionLeadingIcon(o)}
                                        <span>{o.label}</span>
                                    </span>
                                </SelectItem>
                            ))}
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </>
        );
    }

    const opt = options.find((o) => o.value === str);
    const display = opt?.label ?? str;
    return (
        <Badge
            variant="outline"
            className="inline-flex max-w-full items-center gap-1.5 px-1.5 font-normal text-muted-foreground"
        >
            {selectOptionLeadingIcon(opt)}
            <span className="truncate">{display}</span>
        </Badge>
    );
}
