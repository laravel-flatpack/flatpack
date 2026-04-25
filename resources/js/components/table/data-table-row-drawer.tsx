import * as React from 'react';
import { SchemaFieldsRenderer } from '@/components/form-fields/schema-fields-renderer';
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
import { useIsMobile } from '@/hooks/use-mobile';
import { mapDataTableColumnToDrawerField } from '@/lib/data-table-row-drawer-field-mapper';
import { formatCellValue, mergeCommittedDate } from '@/lib/data-table-utils';
import { route } from '@/lib/route';
import type {
    DataTableRowDrawerAttachBodyRenderContext,
    DataTableRowDrawerBodyVariant,
    FlatpackDataTableColumn,
} from '@/types/data-table';
import type { SchemaFieldRenderEntry } from '@/types/schema-fields-renderer';

function DrawerRowField({
    col,
    value,
    onChange,
    patchDraft,
    flatpackEntity,
    flatpackTableFieldId,
}: {
    col: FlatpackDataTableColumn;
    value: unknown;
    onChange: (next: unknown) => void;
    /** Merge multiple keys in one update (e.g. relation FK + nested `row[relation]` for display). */
    patchDraft: (patch: Record<string, unknown>) => void;
    flatpackEntity?: string;
    flatpackTableFieldId?: string;
}): SchemaFieldRenderEntry | null {
    const mapped = mapDataTableColumnToDrawerField(col);
    if (mapped?.kind !== 'form') {
        return null;
    }
    const fieldId = `drawer-field-${col.id}`;
    const baseEntry: SchemaFieldRenderEntry = {
        id: fieldId,
        field: mapped.field,
        value,
        serializeValue: (field, nextValue, currentValue) => {
            if (field.type === 'date-picker') {
                const nextDate =
                    typeof nextValue === 'object' &&
                    nextValue instanceof Date &&
                    !Number.isNaN(nextValue.getTime())
                        ? `${nextValue.getFullYear()}-${String(nextValue.getMonth() + 1).padStart(2, '0')}-${String(nextValue.getDate()).padStart(2, '0')}`
                        : '';
                return mergeCommittedDate(nextDate, currentValue);
            }
            return nextValue;
        },
        onValueChange: (nextSerializedValue: unknown) =>
            onChange(nextSerializedValue),
    };

    if (
        mapped.field.type === 'combobox' &&
        col.type === 'relation' &&
        col.relation &&
        col.relationName &&
        col.relationValue
    ) {
        const relationKey = col.relation;
        const relationValueKey = col.relationValue;
        const relationNameKey = col.relationName;
        const e = flatpackEntity?.trim() ?? '';
        const tf = flatpackTableFieldId?.trim() ?? '';
        const extraComponentProps: Record<string, unknown> = {
            remote: true,
            remoteEndpoint:
                e !== ''
                    ? route(
                          'flatpack.entities.embedded-table-relation-options',
                          {
                              entity: e,
                          },
                      )
                    : undefined,
            remoteFieldParamKey: null,
            remoteSearchParamKey: 'q',
            remoteBaseParams:
                tf !== ''
                    ? {
                          table_field: tf,
                          column_id: col.id,
                      }
                    : undefined,
        };
        return {
            ...baseEntry,
            extraComponentProps,
            onValueChange: (nextSerializedValue: unknown) => {
                if (
                    nextSerializedValue !== null &&
                    typeof nextSerializedValue === 'object' &&
                    'value' in
                        (nextSerializedValue as Record<string, unknown>) &&
                    'label' in (nextSerializedValue as Record<string, unknown>)
                ) {
                    const v = String(
                        (nextSerializedValue as Record<string, unknown>).value,
                    );
                    const label = String(
                        (nextSerializedValue as Record<string, unknown>).label,
                    );
                    patchDraft({
                        [col.id]: v,
                        [relationKey]: {
                            [relationValueKey]: v,
                            [relationNameKey]: label,
                        },
                    });
                    return;
                }
                if (nextSerializedValue == null || nextSerializedValue === '') {
                    patchDraft({
                        [col.id]: '',
                        [relationKey]: null,
                    });
                    return;
                }
                onChange(nextSerializedValue);
            },
        };
    }

    return baseEntry;
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
    flatpackEntity?: string;
    flatpackTableFieldId?: string;
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
    flatpackEntity,
    flatpackTableFieldId,
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

    const patchDraft = React.useCallback((patch: Record<string, unknown>) => {
        setDraft((d) => ({ ...d, ...patch }));
    }, []);

    const formColumns = schemaColumns.filter((c) => c.type !== 'actions');
    const drawerEntries = formColumns
        .map((c) =>
            DrawerRowField({
                col: c,
                value: draft[c.id],
                onChange: (v) => setField(c.id, v),
                patchDraft,
                flatpackEntity,
                flatpackTableFieldId,
            }),
        )
        .filter((entry): entry is SchemaFieldRenderEntry => entry !== null);
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
                    <DrawerTitle>{titleColumn.label}</DrawerTitle>
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
                        {showAttachSlot ? (
                            renderAttachBody(attachContext)
                        ) : (
                            <>
                                <SchemaFieldsRenderer entries={drawerEntries} />
                                {formColumns
                                    .filter(
                                        (c) =>
                                            mapDataTableColumnToDrawerField(
                                                c,
                                            ) == null,
                                    )
                                    .map((c) => (
                                        <div
                                            key={`read-${c.id}`}
                                            className="flex flex-col gap-1"
                                        >
                                            <span className="text-xs font-medium text-muted-foreground">
                                                {c.label}
                                            </span>
                                            <span className="text-foreground">
                                                {formatCellValue(draft[c.id]) ||
                                                    '—'}
                                            </span>
                                        </div>
                                    ))}
                            </>
                        )}
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
