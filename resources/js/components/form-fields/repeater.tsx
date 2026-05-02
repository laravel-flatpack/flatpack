import {
    closestCenter,
    DndContext,
    type DragEndEvent,
    KeyboardSensor,
    MouseSensor,
    TouchSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import { restrictToVerticalAxis } from '@dnd-kit/modifiers';
import { arrayMove, SortableContext, useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { ChevronDown, Copy, GripVertical, Plus, Trash2 } from 'lucide-react';
import {
    type CSSProperties,
    type ReactNode,
    useCallback,
    useLayoutEffect,
    useMemo,
    useState,
} from 'react';
import { SchemaFieldsRenderer } from '@/components/form-fields/schema-fields-renderer';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldTitle,
} from '@/components/ui/field';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { selectOptionLeadingIcon } from '@/components/ui/select-option-leading-icon';
import { serializeFieldValue } from '@/lib/form-page-field-values';
import { normalizeYamlFieldType } from '@/lib/form-schema';
import {
    mapRepeaterGroupsById,
    type ParsedRepeaterGroup,
    parseRepeaterGroups,
} from '@/lib/repeater-groups';
import { cn } from '@/lib/utils';
import type { FormFieldProps } from '@/types/form-fields';
import type { SchemaFieldRenderEntry } from '@/types/schema-fields-renderer';

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function newRowId(): string {
    return crypto.randomUUID();
}

function normalizeRepeaterRows(
    raw: unknown,
    minItems: number,
): Record<string, unknown>[] {
    const base: unknown[] = Array.isArray(raw) ? [...raw] : [];
    while (base.length < minItems) {
        base.push({});
    }
    return base.map((row) => (isRecord(row) ? { ...row } : {}));
}

function repeaterTitleForRow(
    row: Record<string, unknown>,
    titleFrom: string | false | undefined,
    index: number,
): string {
    if (titleFrom === false) {
        return '';
    }
    if (
        titleFrom != null &&
        titleFrom !== '' &&
        row[titleFrom] != null &&
        String(row[titleFrom]).trim() !== ''
    ) {
        return String(row[titleFrom]);
    }
    return `Item ${index + 1}`;
}

function RepeaterGroupTypePicker({
    rowIndex,
    row,
    repeaterId,
    groupsList,
    groupKeyAttr,
    onSelectType,
}: {
    rowIndex: number;
    row: Record<string, unknown>;
    repeaterId: string;
    groupsList: ParsedRepeaterGroup[];
    groupKeyAttr: string;
    onSelectType: (rowIndex: number, groupId: string) => void;
}) {
    const raw = row[groupKeyAttr];
    const gid = typeof raw === 'string' ? raw.trim() : '';
    const validIds = new Set(groupsList.map((g) => g.id));
    const value = gid !== '' && validIds.has(gid) ? gid : '';

    const controlId = `${repeaterId}-row-${rowIndex}-group-type`;

    return (
        <div className="col-span-full mb-1 space-y-2">
            <Label
                htmlFor={controlId}
                className="text-muted-foreground text-xs"
            >
                Block type
            </Label>
            <Select
                value={value}
                onValueChange={(v) => onSelectType(rowIndex, v)}
            >
                <SelectTrigger id={controlId} className="w-full max-w-md">
                    <SelectValue placeholder="Choose block type…" />
                </SelectTrigger>
                <SelectContent>
                    {groupsList.map((g) => (
                        <SelectItem key={g.id} value={g.id}>
                            <span className="flex items-center gap-2">
                                {selectOptionLeadingIcon({ icon: g.icon })}
                                <span>{g.label}</span>
                            </span>
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

type RepeaterSortableRowProps = {
    sortableId: string;
    children: (args: {
        setNodeRef: (node: HTMLElement | null) => void;
        rowStyle: CSSProperties;
        isDragging: boolean;
        dragHandle: ReactNode;
    }) => ReactNode;
};

function RepeaterSortableRow({
    sortableId,
    children,
}: RepeaterSortableRowProps) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: sortableId });

    const rowStyle: CSSProperties = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    const dragHandle = (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            className={cn(
                'h-8 w-8 shrink-0 cursor-grab text-muted-foreground hover:bg-transparent active:cursor-grabbing',
            )}
            {...attributes}
            {...listeners}
            aria-label="Drag to reorder row"
        >
            <GripVertical className="size-4" aria-hidden />
        </Button>
    );

    return (
        <>
            {children({
                setNodeRef,
                rowStyle,
                isDragging,
                dragHandle,
            })}
        </>
    );
}

export const RepeaterField = ({
    id,
    label,
    helperText,
    value,
    onValueChange,
    form,
    fields: fieldsProp,
    groups,
    prompt = 'Add new item',
    displayMode = 'accordion',
    itemsExpanded = true,
    titleFrom,
    minItems = 0,
    maxItems,
    showReorder = true,
    showDuplicate = true,
    flatpackEntity,
    parentRecordKey,
    required = false,
    invalid = false,
    groupKeyFrom,
}: {
    id: string;
    label: string;
    helperText?: string;
    value?: unknown;
    onValueChange?: (value: unknown) => void;
    form?: string;
    fields?: Record<string, Record<string, unknown>>;
    groups?: unknown;
    prompt?: string;
    displayMode?: 'accordion' | 'builder';
    itemsExpanded?: boolean;
    titleFrom?: string | false;
    minItems?: number;
    maxItems?: number;
    groupKeyFrom?: string;
    showReorder?: boolean;
    showDuplicate?: boolean;
    flatpackEntity?: string;
    parentRecordKey?: string | null;
    required?: boolean;
    invalid?: boolean;
}) => {
    const labelId = `${id}-label`;

    const groupKeyAttr =
        typeof groupKeyFrom === 'string' && groupKeyFrom.trim() !== ''
            ? groupKeyFrom.trim()
            : '_group';

    const resolvedGroups = useMemo(() => parseRepeaterGroups(groups), [groups]);

    const isGroupMode =
        groups != null &&
        resolvedGroups.status === 'inline' &&
        resolvedGroups.groups.length > 0;

    const groupById = useMemo(() => {
        if (!isGroupMode || resolvedGroups.status !== 'inline') {
            return null;
        }
        return mapRepeaterGroupsById(resolvedGroups.groups);
    }, [isGroupMode, resolvedGroups]);

    const formFields = useMemo(() => {
        if (
            fieldsProp != null &&
            typeof fieldsProp === 'object' &&
            !Array.isArray(fieldsProp) &&
            Object.keys(fieldsProp).length > 0
        ) {
            return fieldsProp;
        }
        return null;
    }, [fieldsProp]);

    const rows = useMemo(
        () => normalizeRepeaterRows(value, minItems),
        [value, minItems],
    );

    const [rowIds, setRowIds] = useState<string[]>(() =>
        Array.from(
            { length: normalizeRepeaterRows(value, minItems).length },
            () => newRowId(),
        ),
    );

    /** Stable ids for sortable rows and React keys; length tracks `rows` (never persisted). */
    useLayoutEffect(() => {
        setRowIds((prev) => {
            if (prev.length === rows.length) {
                return prev;
            }
            if (prev.length < rows.length) {
                const pad = Array.from(
                    { length: rows.length - prev.length },
                    () => newRowId(),
                );
                return [...prev, ...pad];
            }
            return prev.slice(0, rows.length);
        });
    }, [rows.length]);

    const commitRows = useCallback(
        (next: Record<string, unknown>[]) => {
            onValueChange?.(next);
        },
        [onValueChange],
    );

    const patchRowField = useCallback(
        (rowIndex: number, subKey: string, serialized: unknown) => {
            const next = rows.map((row, i) => {
                if (i !== rowIndex) {
                    return row;
                }
                return { ...row, [subKey]: serialized };
            });
            commitRows(next);
        },
        [rows, commitRows],
    );

    const selectGroupForRow = useCallback(
        (rowIndex: number, newGroupId: string) => {
            const next = rows.map((row, i) => {
                if (i !== rowIndex) {
                    return row;
                }
                return { [groupKeyAttr]: newGroupId };
            });
            commitRows(next);
        },
        [rows, commitRows, groupKeyAttr],
    );

    const removeRow = useCallback(
        (rowIndex: number) => {
            if (rows.length <= minItems) {
                return;
            }
            setRowIds((ids) => ids.filter((_, i) => i !== rowIndex));
            commitRows(rows.filter((_, i) => i !== rowIndex));
        },
        [rows, minItems, commitRows],
    );

    const duplicateRow = useCallback(
        (rowIndex: number) => {
            if (maxItems != null && rows.length >= maxItems) {
                return;
            }
            const newId = newRowId();
            const clone = { ...rows[rowIndex] };
            const next = [
                ...rows.slice(0, rowIndex + 1),
                clone,
                ...rows.slice(rowIndex + 1),
            ];
            setRowIds((ids) => [
                ...ids.slice(0, rowIndex + 1),
                newId,
                ...ids.slice(rowIndex + 1),
            ]);
            commitRows(next);
        },
        [rows, maxItems, commitRows],
    );

    const addRow = useCallback(() => {
        if (maxItems != null && rows.length >= maxItems) {
            return;
        }
        setRowIds((ids) => [...ids, newRowId()]);
        commitRows([...rows, {}]);
    }, [rows, maxItems, commitRows]);

    const nestedEntries = useCallback(
        (
            rowIndex: number,
            row: Record<string, unknown>,
        ): SchemaFieldRenderEntry[] => {
            let source: Record<string, Record<string, unknown>> | null = null;
            if (isGroupMode && groupById) {
                const rawGk = row[groupKeyAttr];
                const gid = typeof rawGk === 'string' ? rawGk.trim() : '';
                if (gid !== '') {
                    source = groupById.get(gid)?.fields ?? null;
                }
            } else {
                source = formFields;
            }
            if (source == null || Object.keys(source).length === 0) {
                return [];
            }
            return Object.entries(source).flatMap(([subKey, raw]) => {
                if (!isRecord(raw)) {
                    return [];
                }
                const t = normalizeYamlFieldType(raw.type);
                if (t === undefined) {
                    return [];
                }
                const field = { ...raw, type: t } as FormFieldProps;
                return [
                    {
                        id: `${id}__${rowIndex}__${subKey}`,
                        field,
                        value: row[subKey],
                        onValueChange: (nextSerialized: unknown) => {
                            patchRowField(rowIndex, subKey, nextSerialized);
                        },
                        serializeValue: (
                            f: FormFieldProps,
                            next: unknown,
                            _cur: unknown,
                        ) => serializeFieldValue(f, next),
                    },
                ];
            });
        },
        [formFields, groupById, groupKeyAttr, id, isGroupMode, patchRowField],
    );

    const canAdd = maxItems == null || rows.length < maxItems;
    const canRemove = rows.length > minItems && rows.length > 0;
    /** Exactly one row allowed and required: no add/reorder/duplicate/remove UI. */
    const singleFixedRow = minItems === 1 && maxItems === 1;
    /** Single-row repeater with `titleFrom: false`: no per-row title bar. */
    const hideSingleRowTitleBar = singleFixedRow && titleFrom === false;

    const showDragReorder = showReorder && !singleFixedRow && rows.length > 1;

    const dndSensors = useSensors(
        useSensor(MouseSensor, {
            activationConstraint: { distance: 8 },
        }),
        useSensor(TouchSensor, {}),
        useSensor(KeyboardSensor, {}),
    );

    const dndContextId = `${id}-repeater-dnd`;

    const onDragEnd = useCallback(
        (event: DragEndEvent) => {
            const { active, over } = event;
            if (!over || active.id === over.id) {
                return;
            }
            const activeId = String(active.id);
            const overId = String(over.id);
            const oldIndex = rowIds.indexOf(activeId);
            const newIndex = rowIds.indexOf(overId);
            if (oldIndex < 0 || newIndex < 0) {
                return;
            }
            setRowIds((ids) => arrayMove(ids, oldIndex, newIndex));
            commitRows(arrayMove(rows, oldIndex, newIndex));
        },
        [rowIds, rows, commitRows],
    );

    if (groups != null && resolvedGroups.status === 'yaml-path') {
        return (
            <Field data-invalid={invalid || undefined}>
                <FieldTitle id={labelId}>{label}</FieldTitle>
                {helperText != null && helperText !== '' ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
                <FieldContent>
                    <FieldDescription>
                        This repeater references a YAML path for{' '}
                        <code className="rounded bg-muted px-1 py-0.5 text-xs">
                            groups
                        </code>{' '}
                        ({resolvedGroups.path}). The panel needs group
                        definitions inlined from the server (resolved{' '}
                        <code className="rounded bg-muted px-1 py-0.5 text-xs">
                            groups:
                        </code>{' '}
                        map or array on the repeater field).
                    </FieldDescription>
                </FieldContent>
            </Field>
        );
    }

    if (
        groups != null &&
        !isGroupMode &&
        resolvedGroups.status !== 'yaml-path'
    ) {
        return (
            <Field data-invalid={invalid || undefined}>
                <FieldTitle id={labelId}>{label}</FieldTitle>
                {helperText != null && helperText !== '' ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
                <FieldContent>
                    <FieldDescription>
                        Repeater{' '}
                        <code className="rounded bg-muted px-1 py-0.5 text-xs">
                            groups
                        </code>{' '}
                        are missing or invalid (each group needs{' '}
                        <code className="rounded bg-muted px-1 py-0.5 text-xs">
                            label
                        </code>{' '}
                        and non-empty{' '}
                        <code className="rounded bg-muted px-1 py-0.5 text-xs">
                            fields
                        </code>
                        ).
                    </FieldDescription>
                </FieldContent>
            </Field>
        );
    }

    if (!isGroupMode && typeof form === 'string') {
        return (
            <Field data-invalid={invalid || undefined}>
                <FieldTitle id={labelId}>{label}</FieldTitle>
                {helperText != null && helperText !== '' ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
                <FieldContent>
                    <FieldDescription>
                        This repeater references a YAML path ({form}); the panel
                        needs item fields inlined from the server (top-level
                        `fields` on the repeater).
                    </FieldDescription>
                </FieldContent>
            </Field>
        );
    }

    if (
        !isGroupMode &&
        (formFields == null || Object.keys(formFields).length === 0)
    ) {
        return (
            <Field data-invalid={invalid || undefined}>
                <FieldTitle id={labelId}>{label}</FieldTitle>
                {helperText != null && helperText !== '' ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
                <FieldContent>
                    <FieldDescription>
                        Repeater has no item fields (set `fields` definition or
                        a `form` path).
                    </FieldDescription>
                </FieldContent>
            </Field>
        );
    }

    const inlineGroupList =
        isGroupMode && resolvedGroups.status === 'inline'
            ? resolvedGroups.groups
            : [];

    const bodyForRow = (rowIndex: number, row: Record<string, unknown>) => (
        <div className="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-4">
            {isGroupMode && inlineGroupList.length > 0 ? (
                <RepeaterGroupTypePicker
                    rowIndex={rowIndex}
                    row={row}
                    repeaterId={id}
                    groupsList={inlineGroupList}
                    groupKeyAttr={groupKeyAttr}
                    onSelectType={selectGroupForRow}
                />
            ) : null}
            <SchemaFieldsRenderer
                entries={nestedEntries(rowIndex, row)}
                spanContext="repeater"
                entity={flatpackEntity}
                parentRecordKey={parentRecordKey ?? undefined}
                modeKey={`${id}-row-${rowIndex}`}
                showErrors={false}
            />
        </div>
    );

    const renderRepeaterRow = (
        row: Record<string, unknown>,
        rowIndex: number,
        dragHandle: ReactNode | null,
    ) => {
        const title = repeaterTitleForRow(row, titleFrom, rowIndex);
        const toolbar = singleFixedRow ? null : (
            <div className="flex flex-wrap items-center gap-1 shrink-0">
                {showDuplicate ? (
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8"
                        disabled={maxItems != null && rows.length >= maxItems}
                        onClick={() => duplicateRow(rowIndex)}
                        aria-label="Duplicate row"
                    >
                        <Copy className="size-4" />
                    </Button>
                ) : null}
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8 text-destructive hover:text-destructive"
                    disabled={!canRemove}
                    onClick={() => removeRow(rowIndex)}
                    aria-label="Remove row"
                >
                    <Trash2 className="size-4" />
                </Button>
            </div>
        );

        if (displayMode === 'builder') {
            return (
                <div
                    className={cn(
                        'rounded-lg border border-border bg-card p-4 space-y-3',
                    )}
                >
                    {hideSingleRowTitleBar ? null : (
                        <div className="flex flex-wrap items-start justify-between gap-2">
                            <span className="font-medium text-sm flex items-center gap-2 min-w-0">
                                {dragHandle}
                                <span className="min-w-0">{title}</span>
                            </span>
                            {toolbar}
                        </div>
                    )}
                    {bodyForRow(rowIndex, row)}
                </div>
            );
        }

        if (hideSingleRowTitleBar) {
            return (
                <div className="rounded-lg border border-border bg-card px-3 py-3 space-y-3">
                    {bodyForRow(rowIndex, row)}
                </div>
            );
        }

        return (
            <Collapsible
                className="group/repeater-row"
                defaultOpen={itemsExpanded}
            >
                <div className="rounded-lg border border-border bg-card">
                    <div className="flex flex-wrap items-center gap-2 px-3 py-2">
                        {dragHandle}
                        <CollapsibleTrigger
                            className={cn(
                                'flex flex-1 min-w-0 items-center gap-2 text-left font-medium text-sm',
                                'hover:underline underline-offset-4',
                            )}
                        >
                            <ChevronDown className="size-4 shrink-0 transition-transform group-data-[state=open]/repeater-row:rotate-180" />
                            <span className="truncate">{title}</span>
                        </CollapsibleTrigger>
                        {toolbar}
                    </div>
                    <CollapsibleContent>
                        <div className="border-t border-border px-3 py-3 space-y-3">
                            {bodyForRow(rowIndex, row)}
                        </div>
                    </CollapsibleContent>
                </div>
            </Collapsible>
        );
    };

    const rowKeys = rows.map(
        (__, rowIndex) => rowIds[rowIndex] ?? `${id}-row-${rowIndex}`,
    );

    const rowsBlock = showDragReorder ? (
        <DndContext
            id={dndContextId}
            sensors={dndSensors}
            collisionDetection={closestCenter}
            modifiers={[restrictToVerticalAxis]}
            onDragEnd={onDragEnd}
        >
            <SortableContext items={rowIds}>
                {rows.map((row, rowIndex) => {
                    const rowKey = rowKeys[rowIndex];
                    return (
                        <RepeaterSortableRow key={rowKey} sortableId={rowKey}>
                            {({
                                setNodeRef,
                                rowStyle,
                                isDragging,
                                dragHandle,
                            }) => (
                                <div
                                    ref={setNodeRef}
                                    style={rowStyle}
                                    className={cn(
                                        isDragging &&
                                            'relative z-10 opacity-80',
                                    )}
                                >
                                    {renderRepeaterRow(
                                        row,
                                        rowIndex,
                                        dragHandle,
                                    )}
                                </div>
                            )}
                        </RepeaterSortableRow>
                    );
                })}
            </SortableContext>
        </DndContext>
    ) : (
        rows.map((row, rowIndex) => (
            <div key={rowKeys[rowIndex]} className="space-y-3">
                {renderRepeaterRow(row, rowIndex, null)}
            </div>
        ))
    );

    return (
        <Field
            className="pointer-events-auto"
            data-invalid={invalid || undefined}
        >
            <FieldTitle id={labelId}>
                {label}
                {required ? (
                    <span className="text-destructive" aria-hidden="true">
                        {' '}
                        *
                    </span>
                ) : null}
            </FieldTitle>
            {helperText != null && helperText !== '' ? (
                <FieldDescription>{helperText}</FieldDescription>
            ) : null}
            <FieldContent className="space-y-3">
                <div className="space-y-3">{rowsBlock}</div>
                {singleFixedRow ? null : (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={!canAdd}
                        onClick={addRow}
                        className="w-full sm:w-auto"
                    >
                        <Plus className="size-4 mr-2" />
                        {prompt}
                    </Button>
                )}
            </FieldContent>
        </Field>
    );
};
