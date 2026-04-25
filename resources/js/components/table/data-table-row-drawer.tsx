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
import type {
    DataTableRowDrawerAttachBodyRenderContext,
    DataTableRowDrawerBodyVariant,
    FlatpackDataTableColumn,
} from '@/types/data-table';

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

export type DataTableRowDrawerPanelProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** Optional trigger rendered inside the drawer root (e.g. link-style cell opener). */
    trigger?: React.ReactNode;
    row: Record<string, unknown>;
    rowId: string;
    schemaColumns: FlatpackDataTableColumn[];
    titleColumn: FlatpackDataTableColumn;
    onRowReplace: (rowId: string, nextRow: Record<string, unknown>) => void;
    /** `attachExisting` when toolbar `action: attach` opened the draft; else row click / create/add. */
    bodyVariant?: DataTableRowDrawerBodyVariant;
    /**
     * Renders the main area when `bodyVariant` is `attachExisting` and this is set; otherwise
     * default column fields. Draft save still goes through `onRowReplace` (see `useDataTableRowReplaceFlow` for
     * `onValueChange` timing). BTM row shape: `RelationFormSynchronizer::syncBelongsToMany`.
     */
    renderAttachBody?: (
        ctx: DataTableRowDrawerAttachBodyRenderContext,
    ) => React.ReactNode;
};

/**
 * Controlled drawer shell + row fields (or optional BelongsToMany attach slot). Draft open does not
 * notify the parent `onValueChange` until save — see `useDataTableCreateRowFlow` and `useDataTableRowReplaceFlow`.
 */
export function DataTableRowDrawerPanel({
    open,
    onOpenChange,
    trigger,
    row,
    rowId,
    schemaColumns,
    titleColumn,
    onRowReplace,
    bodyVariant = 'rowFields',
    renderAttachBody,
}: DataTableRowDrawerPanelProps) {
    const isMobile = useIsMobile();
    const [draft, setDraft] = React.useState<Record<string, unknown>>(row);
    const firstFieldsRegionRef = React.useRef<HTMLDivElement>(null);

    React.useLayoutEffect(() => {
        if (open) {
            setDraft({ ...row });
        }
    }, [open, row]);

    React.useLayoutEffect(() => {
        if (!open) {
            return;
        }
        // Move focus off the page before the next frame so the layer that sets
        // aria-hidden on <main> does not see a focused descendant (browser warning).
        const active = document.activeElement;
        if (
            active instanceof HTMLElement &&
            active.closest('[data-slot="drawer-content"]') == null
        ) {
            active.blur();
        }
        const id = window.setTimeout(() => {
            const root = firstFieldsRegionRef.current;
            if (root == null) {
                return;
            }
            const el = root.querySelector<HTMLElement>(
                'input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), [role="combobox"]:not([aria-disabled="true"])',
            );
            el?.focus({ preventScroll: true });
        }, 0);
        return () => {
            window.clearTimeout(id);
        };
    }, [open]);

    const setField = React.useCallback((columnId: string, next: unknown) => {
        setDraft((d) => ({ ...d, [columnId]: next }));
    }, []);

    const formColumns = schemaColumns.filter((c) => c.type !== 'actions');
    const attachContext: DataTableRowDrawerAttachBodyRenderContext = {
        rowId,
        draft,
        setDraft,
        schemaColumns,
        titleColumn,
        bodyVariant,
        onRequestClose: () => {
            onOpenChange(false);
        },
    };
    const showAttachSlot =
        bodyVariant === 'attachExisting' && renderAttachBody != null;

    return (
        <Drawer
            direction={isMobile ? 'bottom' : 'right'}
            onOpenChange={onOpenChange}
            open={open}
        >
            {trigger}
            <DrawerContent>
                <DrawerHeader className="gap-1">
                    <DrawerTitle>
                        {formatCellValue(draft[titleColumn.id]) ||
                            titleColumn.label}
                    </DrawerTitle>
                    <DrawerDescription>
                        {bodyVariant === 'attachExisting'
                            ? 'Add or link the row, then save.'
                            : 'Edit row fields and save your changes.'}
                    </DrawerDescription>
                </DrawerHeader>
                <div className="flex flex-col gap-4 overflow-y-auto px-4 pb-2 text-sm">
                    <div
                        ref={firstFieldsRegionRef}
                        className="flex flex-col gap-4"
                    >
                        {showAttachSlot
                            ? renderAttachBody(attachContext)
                            : formColumns.map((c) => (
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
                            onOpenChange(false);
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
    const [open, setOpen] = React.useState(false);
    const drawerTriggerRef = React.useRef<HTMLButtonElement>(null);

    const openDrawer = React.useCallback(() => {
        drawerTriggerRef.current?.blur();
        setOpen(true);
    }, []);

    return (
        <>
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
            <DataTableRowDrawerPanel
                open={open}
                onOpenChange={setOpen}
                row={row}
                rowId={rowId}
                schemaColumns={schemaColumns}
                titleColumn={triggerColumn}
                onRowReplace={onRowReplace}
            />
        </>
    );
}
