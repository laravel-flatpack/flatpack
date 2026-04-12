import * as React from 'react';
import { Button } from '@/components/ui/button';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useIsMobile } from '@/hooks/use-mobile';
import {
    columnEditableInDrawer,
    dateInputSegment,
    formatCellValue,
    mergeCommittedDate,
} from '@/lib/data-table-utils';
import type { FlatpackDataTableColumn } from '@/types/data-table';

function DrawerRowField({
    col,
    value,
    onChange,
}: {
    col: FlatpackDataTableColumn;
    value: unknown;
    onChange: (next: unknown) => void;
}) {
    const fieldId = `drawer-field-${col.id}`;

    if (
        (col.type === 'select' ||
            (col.type === 'badge' && col.options?.length)) &&
        col.options?.length
    ) {
        const str = value == null ? '' : String(value);
        const validOption = col.options.some((o) => o.value === str);
        return (
            <div className="flex flex-col gap-2">
                <Label htmlFor={fieldId}>{col.label}</Label>
                <Select
                    value={str !== '' && validOption ? str : undefined}
                    onValueChange={(v) => onChange(v)}
                >
                    <SelectTrigger id={fieldId} className="w-full">
                        <SelectValue placeholder="Choose…" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            {col.options.map((o) => (
                                <SelectItem key={o.value} value={o.value}>
                                    {o.label}
                                </SelectItem>
                            ))}
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>
        );
    }

    if (col.type === 'date') {
        const day = dateInputSegment(value);
        return (
            <div className="flex flex-col gap-2">
                <Label htmlFor={fieldId}>{col.label}</Label>
                <Input
                    id={fieldId}
                    type="date"
                    className="w-full tabular-nums"
                    value={day}
                    onChange={(e) =>
                        onChange(mergeCommittedDate(e.target.value, value))
                    }
                />
            </div>
        );
    }

    if (columnEditableInDrawer(col)) {
        const text = formatCellValue(value);
        return (
            <div className="flex flex-col gap-2">
                <Label htmlFor={fieldId}>{col.label}</Label>
                <Input
                    id={fieldId}
                    className="w-full"
                    value={text}
                    onChange={(e) => onChange(e.target.value)}
                />
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-1">
            <span className="text-xs font-medium text-muted-foreground">
                {col.label}
            </span>
            <span className="text-foreground">
                {formatCellValue(value) || '—'}
            </span>
        </div>
    );
}

export function DataTableRowDetailDrawer({
    triggerColumn,
    row,
    rowId,
    schemaColumns,
    onRowReplace,
}: {
    triggerColumn: FlatpackDataTableColumn;
    row: Record<string, unknown>;
    rowId: string;
    schemaColumns: FlatpackDataTableColumn[];
    onRowReplace: (rowId: string, nextRow: Record<string, unknown>) => void;
}) {
    const isMobile = useIsMobile();
    const [open, setOpen] = React.useState(false);
    const [draft, setDraft] = React.useState<Record<string, unknown>>(row);
    const drawerTriggerRef = React.useRef<HTMLButtonElement>(null);

    React.useLayoutEffect(() => {
        if (open) {
            setDraft({ ...row });
        }
    }, [open, row]);

    const setField = React.useCallback((columnId: string, next: unknown) => {
        setDraft((d) => ({ ...d, [columnId]: next }));
    }, []);

    const openDrawer = React.useCallback(() => {
        drawerTriggerRef.current?.blur();
        setOpen(true);
    }, []);

    const formColumns = schemaColumns.filter((c) => c.type !== 'actions');

    return (
        <Drawer
            direction={isMobile ? 'bottom' : 'right'}
            onOpenChange={setOpen}
            open={open}
        >
            <Button
                ref={drawerTriggerRef}
                type="button"
                variant="link"
                aria-expanded={open}
                aria-haspopup="dialog"
                className="h-auto min-h-0 w-fit max-w-full justify-start px-0 py-0 text-left font-normal text-foreground"
                onClick={openDrawer}
            >
                <span className="truncate">
                    {formatCellValue(row[triggerColumn.id]) || '—'}
                </span>
            </Button>
            <DrawerContent>
                <DrawerHeader className="gap-1">
                    <DrawerTitle>
                        {formatCellValue(draft[triggerColumn.id]) ||
                            triggerColumn.label}
                    </DrawerTitle>
                    <DrawerDescription>
                        Edit row fields and save your changes.
                    </DrawerDescription>
                </DrawerHeader>
                <div className="flex flex-col gap-4 overflow-y-auto px-4 pb-2 text-sm">
                    <div className="flex flex-col gap-4">
                        {formColumns.map((c) => (
                            <DrawerRowField
                                key={c.id}
                                col={c}
                                value={draft[c.id]}
                                onChange={(v) => setField(c.id, v)}
                            />
                        ))}
                    </div>
                </div>
                <DrawerFooter>
                    <Button
                        type="button"
                        onClick={() => {
                            onRowReplace(rowId, draft);
                            setOpen(false);
                        }}
                    >
                        Save changes
                    </Button>
                    <DrawerClose asChild>
                        <Button variant="outline">Cancel</Button>
                    </DrawerClose>
                </DrawerFooter>
            </DrawerContent>
        </Drawer>
    );
}
