import { useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { SchemaFieldsRenderer } from '@/components/form-fields/schema-fields-renderer';
import type { MenuIconName } from '@/components/icons/lucide-menu-icon-registry';
import { menuIcons } from '@/components/icons/lucide-menu-icon-registry';
import { useEmbeddedTableToolbarAction } from '@/components/shell/form/embedded-table-toolbar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { focusFirstControlForFieldId } from '@/lib/focus-field-control';
import {
    emptyValueForField,
    evaluateFieldTrigger,
    valuesEqual,
} from '@/lib/form-field-trigger';
import {
    fieldErrorMessages,
    firstVisibleFieldEntryWithValidationError,
    validationErrorsFingerprint,
} from '@/lib/form-schema';
import {
    rowValidationFieldErrorsByStableId,
    rowValidationMessagesByStableId,
    tableFieldErrorState,
} from '@/lib/form-table-errors';
import { fieldIsRequired } from '@/lib/form-validation';
import type { FormFieldsProps } from '@/types/form-fields-props';
import type { SchemaFieldRenderEntry } from '@/types/schema-fields-renderer';

export function FormFields({
    entity,
    mode,
    record,
    tabPanels,
    fields,
    fieldComponents,
    fieldErrors,
    formValues,
    setFieldValue,
    onEmbeddedTableToolbarAction: onEmbeddedTableToolbarActionProp,
}: FormFieldsProps) {
    const onEmbeddedTableToolbarActionFromContext =
        useEmbeddedTableToolbarAction();
    const onEmbeddedTableToolbarAction =
        onEmbeddedTableToolbarActionProp ??
        onEmbeddedTableToolbarActionFromContext;

    useEffect(() => {
        for (const { id, field } of fields) {
            const triggerState = evaluateFieldTrigger(
                field.trigger,
                formValues,
            );
            if (!triggerState.shouldEmpty) {
                continue;
            }
            const nextEmptyValue = emptyValueForField(field);
            if (valuesEqual(formValues[id], nextEmptyValue)) {
                continue;
            }
            setFieldValue(field, id, nextEmptyValue);
        }
    }, [fields, formValues, setFieldValue]);

    const entries: SchemaFieldRenderEntry[] = useMemo(
        () =>
            fields.map(({ id, field }) => ({
                ...(() => {
                    const triggerState = evaluateFieldTrigger(
                        field.trigger,
                        formValues,
                    );
                    return {
                        hidden: !triggerState.visible,
                        disabled: triggerState.disabled,
                    };
                })(),
                ...(field.type === 'table'
                    ? (() => {
                          const errorState = tableFieldErrorState(
                              fieldErrors,
                              id,
                          );
                          return {
                              extraComponentProps: {
                                  rowValidationMessagesById:
                                      rowValidationMessagesByStableId(
                                          formValues[id],
                                          errorState,
                                      ),
                                  rowValidationFieldErrorsById:
                                      rowValidationFieldErrorsByStableId(
                                          formValues[id],
                                          errorState,
                                      ),
                              },
                          };
                      })()
                    : {}),
                id,
                field,
                value: formValues[id],
                onValueChange: (nextValue: unknown) => {
                    const triggerState = evaluateFieldTrigger(
                        field.trigger,
                        formValues,
                    );
                    if (triggerState.disabled) {
                        return;
                    }
                    setFieldValue(field, id, nextValue);
                },
                required: fieldIsRequired(field),
                invalid: fieldErrorMessages(fieldErrors, id).length > 0,
                errors: fieldErrorMessages(fieldErrors, id),
            })),
        [fields, fieldErrors, formValues, setFieldValue],
    );

    const entryById = useMemo(
        () => new Map(entries.map((e) => [e.id, e])),
        [entries],
    );

    const { unassignedEntries, tabBlocks } = useMemo(() => {
        if (tabPanels === undefined || tabPanels.length === 0) {
            return {
                unassignedEntries: entries,
                tabBlocks: [] as Array<{
                    panelId: string;
                    entries: SchemaFieldRenderEntry[];
                }>,
            };
        }
        const assigned = new Set<string>();
        for (const p of tabPanels) {
            for (const fid of p.field_ids) {
                assigned.add(fid);
            }
        }
        const unassignedEntries = entries.filter((e) => !assigned.has(e.id));
        const tabBlocks = tabPanels.map((p) => ({
            panelId: p.id,
            entries: p.field_ids
                .map((fid) => entryById.get(fid))
                .filter((e): e is SchemaFieldRenderEntry => e !== undefined),
        }));

        return { unassignedEntries, tabBlocks };
    }, [entries, entryById, tabPanels]);

    const orderedEntriesForErrorNav = useMemo((): SchemaFieldRenderEntry[] => {
        if (tabPanels === undefined || tabPanels.length === 0) {
            return entries;
        }
        return [
            ...unassignedEntries,
            ...tabBlocks.flatMap((block) => block.entries),
        ];
    }, [tabPanels, entries, unassignedEntries, tabBlocks]);

    const [activeTab, setActiveTab] = useState(() => tabPanels?.[0]?.id ?? '');
    const activeTabRef = useRef(activeTab);
    activeTabRef.current = activeTab;

    useEffect(() => {
        if (tabPanels === undefined || tabPanels.length === 0) {
            return;
        }
        setActiveTab((cur) =>
            tabPanels.some((p) => p.id === cur)
                ? cur
                : (tabPanels[0]?.id ?? ''),
        );
    }, [tabPanels]);

    const previousErrorFingerprintRef = useRef('');
    const pendingFocusFieldIdRef = useRef<string | null>(null);
    const [focusAfterTabChangeTick, setFocusAfterTabChangeTick] = useState(0);

    useLayoutEffect(() => {
        const fp = validationErrorsFingerprint(fieldErrors);
        if (fp === previousErrorFingerprintRef.current) {
            return;
        }
        previousErrorFingerprintRef.current = fp;
        if (fp === '') {
            return;
        }

        const entry = firstVisibleFieldEntryWithValidationError(
            orderedEntriesForErrorNav,
            fieldErrors,
        );
        if (entry === undefined) {
            return;
        }

        if (tabPanels !== undefined && tabPanels.length > 0) {
            const panel = tabPanels.find((p) => p.field_ids.includes(entry.id));
            if (panel !== undefined && panel.id !== activeTabRef.current) {
                pendingFocusFieldIdRef.current = entry.id;
                setActiveTab(panel.id);
                setFocusAfterTabChangeTick((n) => n + 1);
                return;
            }
        }

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                focusFirstControlForFieldId(entry.id);
            });
        });
    }, [fieldErrors, orderedEntriesForErrorNav, tabPanels]);

    useLayoutEffect(() => {
        if (focusAfterTabChangeTick === 0) {
            return;
        }
        const id = pendingFocusFieldIdRef.current;
        if (id === null) {
            return;
        }
        pendingFocusFieldIdRef.current = null;
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                focusFirstControlForFieldId(id);
            });
        });
    }, [focusAfterTabChangeTick]);

    const renderFields = (subset: SchemaFieldRenderEntry[]) => (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-5 gap-6">
            <SchemaFieldsRenderer
                entries={subset}
                spanContext="page"
                entity={entity}
                parentRecordKey={record}
                modeKey={`${mode}:${record ?? 'new'}`}
                onEmbeddedTableToolbarAction={onEmbeddedTableToolbarAction}
                fieldComponents={fieldComponents}
                showErrors
            />
        </div>
    );

    if (tabPanels === undefined || tabPanels.length === 0) {
        return renderFields(entries);
    }

    return (
        <div className="flex flex-col gap-6">
            {unassignedEntries.length > 0
                ? renderFields(unassignedEntries)
                : null}
            <Tabs
                value={activeTab}
                onValueChange={setActiveTab}
                className="w-full"
            >
                <div className="w-full overflow-x-auto">
                    <TabsList
                        variant="line"
                        className="inline-flex w-max min-w-max flex-nowrap justify-start"
                    >
                        {tabPanels.map((panel) => {
                            const Icon =
                                panel.icon != null && panel.icon in menuIcons
                                    ? menuIcons[panel.icon as MenuIconName]
                                    : null;
                            return (
                                <TabsTrigger
                                    key={panel.id}
                                    value={panel.id}
                                    className="flex-none"
                                >
                                    {Icon != null ? (
                                        <Icon
                                            data-icon="inline-start"
                                            className="size-4"
                                        />
                                    ) : null}
                                    {panel.label}
                                </TabsTrigger>
                            );
                        })}
                    </TabsList>
                </div>
                {tabBlocks.map((block) => (
                    <TabsContent
                        key={block.panelId}
                        value={block.panelId}
                        className="flex flex-col gap-6 pt-4 px-2"
                    >
                        {renderFields(block.entries)}
                    </TabsContent>
                ))}
            </Tabs>
        </div>
    );
}
