import { ChevronDown, Copy, Plus, Trash2 } from 'lucide-react';
import { useCallback, useMemo } from 'react';
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
import { serializeFieldValue } from '@/lib/form-page-field-values';
import { normalizeYamlFieldType } from '@/lib/form-schema';
import { cn } from '@/lib/utils';
import type { FormFieldProps } from '@/types/form-fields';
import type { SchemaFieldRenderEntry } from '@/types/schema-fields-renderer';

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
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

    const removeRow = useCallback(
        (rowIndex: number) => {
            if (rows.length <= minItems) {
                return;
            }
            commitRows(rows.filter((_, i) => i !== rowIndex));
        },
        [rows, minItems, commitRows],
    );

    const duplicateRow = useCallback(
        (rowIndex: number) => {
            if (maxItems != null && rows.length >= maxItems) {
                return;
            }
            const clone = { ...rows[rowIndex] };
            const next = [
                ...rows.slice(0, rowIndex + 1),
                clone,
                ...rows.slice(rowIndex + 1),
            ];
            commitRows(next);
        },
        [rows, maxItems, commitRows],
    );

    const moveRow = useCallback(
        (from: number, delta: number) => {
            const to = from + delta;
            if (to < 0 || to >= rows.length) {
                return;
            }
            const next = [...rows];
            const [removed] = next.splice(from, 1);
            next.splice(to, 0, removed);
            commitRows(next);
        },
        [rows, commitRows],
    );

    const addRow = useCallback(() => {
        if (maxItems != null && rows.length >= maxItems) {
            return;
        }
        commitRows([...rows, {}]);
    }, [rows, maxItems, commitRows]);

    const nestedEntries = useCallback(
        (
            rowIndex: number,
            row: Record<string, unknown>,
        ): SchemaFieldRenderEntry[] => {
            if (formFields == null) {
                return [];
            }
            return Object.entries(formFields).flatMap(([subKey, raw]) => {
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
        [formFields, id, patchRowField],
    );

    const canAdd = maxItems == null || rows.length < maxItems;
    const canRemove = rows.length > minItems && rows.length > 0;
    /** Exactly one row allowed and required: no add/reorder/duplicate/remove UI. */
    const singleFixedRow = minItems === 1 && maxItems === 1;
    /** Single-row repeater with `titleFrom: false`: no per-row title bar. */
    const hideSingleRowTitleBar = singleFixedRow && titleFrom === false;

    if (groups != null) {
        return (
            <Field data-invalid={invalid || undefined}>
                <FieldTitle id={labelId}>{label}</FieldTitle>
                {helperText != null && helperText !== '' ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
                <FieldContent>
                    <FieldDescription>
                        Repeater group mode is not implemented in the UI yet.
                    </FieldDescription>
                </FieldContent>
            </Field>
        );
    }

    if (typeof form === 'string') {
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

    if (formFields == null || Object.keys(formFields).length === 0) {
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

    const bodyForRow = (rowIndex: number, row: Record<string, unknown>) => (
        <SchemaFieldsRenderer
            entries={nestedEntries(rowIndex, row)}
            entity={flatpackEntity}
            parentRecordKey={parentRecordKey ?? undefined}
            modeKey={`${id}-row-${rowIndex}`}
            showErrors={false}
        />
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
                <div className="space-y-3">
                    {rows.map((row, rowIndex) => {
                        const title = repeaterTitleForRow(
                            row,
                            titleFrom,
                            rowIndex,
                        );
                        const toolbar = singleFixedRow ? null : (
                            <div className="flex flex-wrap items-center gap-1 shrink-0">
                                {showReorder ? (
                                    <>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="h-8 px-2"
                                            disabled={rowIndex === 0}
                                            onClick={() =>
                                                moveRow(rowIndex, -1)
                                            }
                                        >
                                            Up
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="h-8 px-2"
                                            disabled={
                                                rowIndex >= rows.length - 1
                                            }
                                            onClick={() => moveRow(rowIndex, 1)}
                                        >
                                            Down
                                        </Button>
                                    </>
                                ) : null}
                                {showDuplicate ? (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="h-8 w-8"
                                        disabled={
                                            maxItems != null &&
                                            rows.length >= maxItems
                                        }
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
                                    key={`${id}-builder-${rowIndex}`}
                                    className={cn(
                                        'rounded-lg border border-border bg-card p-4 space-y-3',
                                    )}
                                >
                                    {hideSingleRowTitleBar ? null : (
                                        <div className="flex flex-wrap items-start justify-between gap-2">
                                            <span className="font-medium text-sm">
                                                {title}
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
                                <div
                                    key={`${id}-acc-plain-${rowIndex}`}
                                    className="rounded-lg border border-border bg-card px-3 py-3 space-y-3"
                                >
                                    {bodyForRow(rowIndex, row)}
                                </div>
                            );
                        }

                        return (
                            <Collapsible
                                key={`${id}-acc-${rowIndex}`}
                                className="group/repeater-row"
                                defaultOpen={itemsExpanded}
                            >
                                <div className="rounded-lg border border-border bg-card">
                                    <div className="flex flex-wrap items-center gap-2 px-3 py-2">
                                        <CollapsibleTrigger
                                            className={cn(
                                                'flex flex-1 min-w-0 items-center gap-2 text-left font-medium text-sm',
                                                'hover:underline underline-offset-4',
                                            )}
                                        >
                                            <ChevronDown className="size-4 shrink-0 transition-transform group-data-[state=open]/repeater-row:rotate-180" />
                                            <span className="truncate">
                                                {title}
                                            </span>
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
                    })}
                </div>
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
