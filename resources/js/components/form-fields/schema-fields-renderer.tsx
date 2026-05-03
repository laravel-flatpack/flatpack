import { ChevronDownIcon } from 'lucide-react';
import { Fragment, Suspense, useId, useMemo } from 'react';
import type { MenuIconName } from '@/components/icons/lucide-menu-icon-registry';
import { menuIcons } from '@/components/icons/lucide-menu-icon-registry';
import { FieldLoading } from '@/components/loading/field-loading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { FieldError } from '@/components/ui/field';
import { InlineFieldsetLabelGridProvider } from '@/contexts/inline-fieldset-label-grid';
import type { FieldSpanContext } from '@/lib/field-span';
import { canonicalizeSpan, spanClassFor } from '@/lib/field-span';
import { loadField } from '@/lib/form';
import { mapFormFieldPropsToComponentProps } from '@/lib/form-field-props';
import {
    type FieldsetBlock,
    groupEntriesByFieldset,
} from '@/lib/form-fieldset';
import {
    componentValueProps,
    relationRemoteProps,
    serializeFieldValue,
} from '@/lib/form-page-field-values';
import { cn } from '@/lib/utils';
import type { FormFieldProps } from '@/types/form-fields';
import type {
    SchemaFieldRenderEntry,
    SchemaFieldsRendererProps,
} from '@/types/schema-fields-renderer';

function spanFromField(field: FormFieldProps): unknown {
    const record = field as Record<string, unknown>;

    return record.span;
}

/** Shared label/control column widths when every visible field uses {@code showLabel: inline}. */
function entriesAllShowLabelInline(entries: SchemaFieldRenderEntry[]): boolean {
    const visible = entries.filter((e) => e.hidden !== true);
    if (visible.length === 0) {
        return false;
    }
    const skipTypes = new Set(['checkbox', 'switch', 'toolbar']);

    return visible.every((e) => {
        if (skipTypes.has(e.field.type)) {
            return false;
        }
        const showLabel = (e.field as { showLabel?: string }).showLabel;

        return showLabel === 'inline';
    });
}

/** Stable segment for grouping identity (`nc` = not collapsible). */
function fieldsetCollapseKey(
    block: Extract<FieldsetBlock, { kind: 'fieldset' }>,
): string {
    if (block.collapsed === undefined) {
        return 'nc';
    }

    return block.collapsed ? 'c1' : 'c0';
}

function stableFieldsetBlockKey(block: FieldsetBlock): string {
    if (block.kind === 'plain') {
        return `plain-${block.entries.map((e) => e.id).join('-')}`;
    }

    return `fieldset-${block.label}-${block.variant}-${fieldsetCollapseKey(block)}-${block.entries.map((e) => e.id).join('-')}`;
}

function FieldsetChevron({
    triggerHoverTextClass,
}: {
    /** Named group `group/trigger` on the button; shifts icon color with label on hover (no bg). */
    triggerHoverTextClass: string;
}) {
    return (
        <ChevronDownIcon
            className={cn(
                'size-4 shrink-0 text-muted-foreground transition-[transform,color] duration-200 group-data-[state=open]:rotate-180',
                triggerHoverTextClass,
            )}
            aria-hidden
        />
    );
}

function gridClassForSpanContext(
    spanContext: FieldSpanContext,
    options?: { compact?: boolean },
): string {
    const compact = options?.compact === true;
    switch (spanContext) {
        case 'page':
            return compact
                ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-5 gap-4'
                : 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-5 gap-6';
        case 'repeater':
            return compact
                ? 'grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-3'
                : 'grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-4';
        case 'drawer':
            return compact
                ? 'grid grid-cols-1 gap-3'
                : 'grid grid-cols-1 gap-4';
        case 'dashboard':
            return compact
                ? 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3'
                : 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4';
        default:
            return compact
                ? 'grid grid-cols-1 gap-3'
                : 'grid grid-cols-1 gap-4';
    }
}

function buildFieldComponentProps(
    entry: SchemaFieldRenderEntry,
    args: {
        entity?: string;
        parentRecordKey?: string | null;
        comboboxDropdownAlign?: 'start' | 'end';
        onEmbeddedTableToolbarAction?: (ctx: {
            fieldId: string;
            actionId: string;
        }) => void;
    },
) {
    const { field, id, value } = entry;
    const serialize =
        entry.serializeValue ?? ((f, next) => serializeFieldValue(f, next));
    const onValueChange = (nextValue: unknown) => {
        entry.onValueChange(serialize(field, nextValue, value));
    };

    return {
        ...(entry.extraComponentProps ?? {}),
        ...mapFormFieldPropsToComponentProps(field, {
            fieldId: id,
            entity: args.entity,
            onValueChange,
            parentRecordKey: args.parentRecordKey,
            onEmbeddedTableToolbarAction: args.onEmbeddedTableToolbarAction,
        }),
        ...componentValueProps(field, value),
        ...(args.entity != null
            ? relationRemoteProps(field, id, args.entity)
            : {}),
        ...(entry.required === true ? { required: true } : {}),
        ...(entry.invalid === true ? { invalid: true } : {}),
        ...(entry.disabled === true ? { disabled: true } : {}),
        ...(field.type === 'combobox' &&
        args.comboboxDropdownAlign !== undefined
            ? { comboboxDropdownAlign: args.comboboxDropdownAlign }
            : {}),
    };
}

export function SchemaFieldsRenderer({
    entries,
    spanContext = 'page',
    comboboxDropdownAlign,
    entity,
    parentRecordKey,
    modeKey,
    onEmbeddedTableToolbarAction,
    fieldComponents,
    showErrors = false,
}: SchemaFieldsRendererProps) {
    const baseId = useId();
    const blocks = useMemo(() => groupEntriesByFieldset(entries), [entries]);

    const renderEntryRow = (
        entry: SchemaFieldRenderEntry,
        options?: { inlineLabelGrid?: boolean },
    ) => {
        const FieldComponent =
            fieldComponents?.[entry.id] ?? loadField(entry.field.type);
        if (entry.hidden === true) {
            return null;
        }
        const inlineLabelGrid = options?.inlineLabelGrid === true;
        const componentProps = buildFieldComponentProps(entry, {
            entity,
            parentRecordKey,
            comboboxDropdownAlign,
            onEmbeddedTableToolbarAction,
        });
        const spanClass = spanClassFor(
            canonicalizeSpan(spanFromField(entry.field)),
            spanContext,
        );
        const rowKey = `${entry.id}:${modeKey ?? 'default'}`;
        const suspenseFallback = inlineLabelGrid ? (
            <div className="col-span-2 h-10 max-w-full animate-pulse rounded-md bg-muted" />
        ) : (
            <FieldLoading {...entry.field} />
        );

        const body = (
            <>
                <Suspense fallback={suspenseFallback}>
                    <FieldComponent {...componentProps} />
                </Suspense>
                {showErrors ? (
                    <div
                        className={cn(
                            inlineLabelGrid && 'col-span-1 col-start-2 min-w-0',
                        )}
                    >
                        <FieldError errors={entry.errors ?? []} />
                    </div>
                ) : null}
            </>
        );

        if (inlineLabelGrid) {
            return <Fragment key={rowKey}>{body}</Fragment>;
        }

        return (
            <div
                key={rowKey}
                className={cn(
                    entry.disabled === true
                        ? 'space-y-2 pointer-events-none opacity-60'
                        : 'space-y-2',
                    spanClass,
                )}
                aria-disabled={entry.disabled === true || undefined}
            >
                {body}
            </div>
        );
    };

    const inlineLabelGridClasses = {
        default:
            'grid w-full grid-cols-[auto_minmax(0,1fr)] items-start gap-x-4 gap-y-6',
        compact:
            'grid w-full grid-cols-[auto_minmax(0,1fr)] items-start gap-x-3 gap-y-4',
    } as const;

    const renderGrid = (
        subset: SchemaFieldRenderEntry[],
        options?: { compact?: boolean },
    ) => {
        const compact = options?.compact === true;
        const gridClass = gridClassForSpanContext(spanContext, { compact });
        const inlineLabelGridClass = compact
            ? inlineLabelGridClasses.compact
            : inlineLabelGridClasses.default;

        if (entriesAllShowLabelInline(subset)) {
            return (
                <InlineFieldsetLabelGridProvider>
                    <div
                        className={inlineLabelGridClass}
                        data-slot="inline-fieldset-label-grid"
                    >
                        {subset.map((e) =>
                            renderEntryRow(e, { inlineLabelGrid: true }),
                        )}
                    </div>
                </InlineFieldsetLabelGridProvider>
            );
        }

        return (
            <div className={gridClass}>
                {subset.map((e) => renderEntryRow(e))}
            </div>
        );
    };

    if (blocks.length === 1 && blocks[0]?.kind === 'plain') {
        return renderGrid(blocks[0].entries);
    }

    return (
        <div className="flex w-full flex-col gap-6">
            {blocks.map((block, blockIndex) => {
                if (block.kind === 'plain') {
                    return (
                        <div key={stableFieldsetBlockKey(block)}>
                            {renderGrid(block.entries)}
                        </div>
                    );
                }

                const FieldIcon =
                    block.icon != null &&
                    block.icon !== '' &&
                    block.icon in menuIcons
                        ? menuIcons[block.icon as MenuIconName]
                        : null;

                const firstEntryId = block.entries[0]?.id ?? '-field';
                const headingId = `${baseId}-fieldset-title-${firstEntryId}`;

                if (block.variant === 'none') {
                    return (
                        <fieldset
                            key={stableFieldsetBlockKey(block)}
                            className="m-0 min-w-0 space-y-0 border-0 p-0"
                        >
                            <legend className="sr-only">{block.label}</legend>
                            {renderGrid(block.entries)}
                        </fieldset>
                    );
                }

                if (block.variant === 'minimal') {
                    const collapsible = block.collapsed !== undefined;
                    if (collapsible) {
                        return (
                            <Collapsible
                                key={stableFieldsetBlockKey(block)}
                                defaultOpen={block.collapsed === false}
                                className="group w-full"
                                data-slot="collapsible"
                            >
                                <section
                                    aria-labelledby={headingId}
                                    className="flex w-full flex-col gap-2"
                                >
                                    <CollapsibleTrigger asChild>
                                        <button
                                            type="button"
                                            id={headingId}
                                            className="group/trigger flex w-full items-center justify-between gap-2 rounded-xl px-1 py-1 text-left font-heading text-sm font-medium outline-none transition-colors hover:text-primary focus-visible:ring-2 focus-visible:ring-ring [&_[data-icon]]:transition-colors [&_[data-icon]]:group-hover/trigger:text-primary"
                                        >
                                            <span className="flex min-w-0 flex-1 items-center gap-2">
                                                {FieldIcon != null ? (
                                                    <FieldIcon
                                                        data-icon="inline-start"
                                                        className="size-4 shrink-0 text-muted-foreground"
                                                        aria-hidden
                                                    />
                                                ) : null}
                                                <span>{block.label}</span>
                                            </span>
                                            <FieldsetChevron triggerHoverTextClass="group-hover/trigger:text-primary" />
                                        </button>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        {renderGrid(block.entries, {
                                            compact: true,
                                        })}
                                    </CollapsibleContent>
                                </section>
                            </Collapsible>
                        );
                    }

                    return (
                        <section
                            key={stableFieldsetBlockKey(block)}
                            aria-labelledby={headingId}
                            className="flex w-full flex-col gap-2"
                        >
                            <div
                                id={headingId}
                                className="flex items-center gap-2 font-heading text-sm font-medium mb-2"
                            >
                                {FieldIcon != null ? (
                                    <FieldIcon
                                        data-icon="inline-start"
                                        className="size-4 shrink-0 text-muted-foreground"
                                        aria-hidden
                                    />
                                ) : null}
                                <span>{block.label}</span>
                            </div>
                            {renderGrid(block.entries, { compact: true })}
                        </section>
                    );
                }

                if (block.variant === 'plain') {
                    const collapsible = block.collapsed !== undefined;
                    const sectionClass = cn(
                        'flex w-full flex-col gap-4',
                        blockIndex > 0 && 'border-border border-t pt-6',
                    );
                    if (collapsible) {
                        return (
                            <Collapsible
                                key={stableFieldsetBlockKey(block)}
                                defaultOpen={block.collapsed === false}
                                className="group w-full"
                                data-slot="collapsible"
                            >
                                <section
                                    aria-labelledby={headingId}
                                    className={sectionClass}
                                >
                                    <CollapsibleTrigger asChild>
                                        <button
                                            type="button"
                                            id={headingId}
                                            className="group/trigger flex w-full items-center justify-between gap-2 rounded-xl px-1 py-1 text-left font-semibold text-base outline-none transition-colors hover:text-primary focus-visible:ring-2 focus-visible:ring-ring [&_[data-icon]]:transition-colors [&_[data-icon]]:group-hover/trigger:text-primary"
                                        >
                                            <span className="flex min-w-0 flex-1 items-center gap-2">
                                                {FieldIcon != null ? (
                                                    <FieldIcon
                                                        data-icon="inline-start"
                                                        className="size-4 shrink-0 text-muted-foreground"
                                                        aria-hidden
                                                    />
                                                ) : null}
                                                <span>{block.label}</span>
                                            </span>
                                            <FieldsetChevron triggerHoverTextClass="group-hover/trigger:text-primary" />
                                        </button>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        {renderGrid(block.entries)}
                                    </CollapsibleContent>
                                </section>
                            </Collapsible>
                        );
                    }

                    return (
                        <section
                            key={stableFieldsetBlockKey(block)}
                            aria-labelledby={headingId}
                            className={sectionClass}
                        >
                            <div
                                id={headingId}
                                className="flex items-center gap-2 font-semibold text-base"
                            >
                                {FieldIcon != null ? (
                                    <FieldIcon
                                        data-icon="inline-start"
                                        className="size-4 shrink-0 text-muted-foreground"
                                        aria-hidden
                                    />
                                ) : null}
                                <span>{block.label}</span>
                            </div>
                            {renderGrid(block.entries)}
                        </section>
                    );
                }

                const collapsibleCard = block.collapsed !== undefined;
                if (collapsibleCard) {
                    return (
                        <Collapsible
                            key={stableFieldsetBlockKey(block)}
                            defaultOpen={block.collapsed === false}
                            className="group w-full"
                            data-slot="collapsible"
                        >
                            <Card size="sm">
                                <CardHeader className="pb-0">
                                    <CollapsibleTrigger asChild>
                                        <button
                                            type="button"
                                            className="group/trigger flex w-full items-center justify-between gap-2 rounded-2xl pb-2 text-left outline-none transition-colors hover:text-primary focus-visible:ring-2 focus-visible:ring-ring [&_[data-icon]]:transition-colors [&_[data-icon]]:group-hover/trigger:text-primary"
                                        >
                                            <CardTitle className="flex-1 text-sm">
                                                <span
                                                    className="flex items-center gap-2"
                                                    id={headingId}
                                                >
                                                    {FieldIcon != null ? (
                                                        <FieldIcon
                                                            data-icon="inline-start"
                                                            className="size-4 shrink-0 text-muted-foreground"
                                                            aria-hidden
                                                        />
                                                    ) : null}
                                                    <span>{block.label}</span>
                                                </span>
                                            </CardTitle>
                                            <FieldsetChevron triggerHoverTextClass="group-hover/trigger:text-primary" />
                                        </button>
                                    </CollapsibleTrigger>
                                </CardHeader>
                                <CollapsibleContent>
                                    <CardContent className="pt-0">
                                        {renderGrid(block.entries)}
                                    </CardContent>
                                </CollapsibleContent>
                            </Card>
                        </Collapsible>
                    );
                }

                return (
                    <Card key={stableFieldsetBlockKey(block)} size="sm">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm">
                                <span
                                    className="flex items-center gap-2"
                                    id={headingId}
                                >
                                    {FieldIcon != null ? (
                                        <FieldIcon
                                            data-icon="inline-start"
                                            className="size-4 shrink-0 text-muted-foreground"
                                            aria-hidden
                                        />
                                    ) : null}
                                    <span>{block.label}</span>
                                </span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0">
                            {renderGrid(block.entries)}
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}
