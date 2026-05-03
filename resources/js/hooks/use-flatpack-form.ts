import type { FormDataConvertible, Page } from '@inertiajs/core';
import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import { useFlatpackPage } from '@/hooks/use-flatpack-page';
import { useFormFieldPresets } from '@/hooks/use-form-field-presets';
import { bypassNextInertiaLeaveGuard } from '@/hooks/use-inertia-leave-guard';
import { loadField } from '@/lib/form';
import { firstErrorMessage } from '@/lib/form-errors';
import {
    buildInitialValues,
    type FlatpackFormTabPanelLayout,
    type FormFieldEntry,
    fieldErrorMessages,
    normalizeFields,
} from '@/lib/form-schema';
import { clientValidationErrors } from '@/lib/form-validation';
import { route } from '@/lib/route';
import type { FormFieldProps } from '@/types/form-fields';
import type {
    FlatpackFormPageProps,
    FlatpackListHeaderAction,
} from '@/types/pages/flatpack';

export type FlatpackFormSubmitIntent = {
    /** YAML action key; optional disambiguator when multiple rows share the same {@code action}. */
    id: string;
    /** Handler name sent as {@code action} (matches config/flatpack.php). */
    action: string;
};

function defaultFormSubmitIntent(
    actions: FlatpackListHeaderAction[],
): FlatpackFormSubmitIntent {
    const withAction = actions.filter(
        (a): a is FlatpackListHeaderAction & { action: string } =>
            'action' in a &&
            typeof (a as { action?: string }).action === 'string' &&
            (a as { action: string }).action !== '' &&
            a.submit === true,
    );
    const primary = withAction.find((a) => a.primary === true);
    if (primary) {
        return { id: primary.id, action: primary.action };
    }
    const save = withAction.find((a) => a.action === 'save');
    if (save) {
        return { id: save.id, action: save.action };
    }
    const first = withAction[0];
    if (first) {
        return { id: first.id, action: first.action };
    }
    return { id: '', action: 'save' };
}

/** Header action waiting for confirm dialog before {@link runSubmit}. */
export type FlatpackFormPendingConfirm = {
    config: FlatpackListHeaderAction & { action: string };
};

/**
 * After a successful Inertia visit, replace client form state with the server’s latest `values`.
 * Call after **form submit** (save) or after **row actions** only when there are no unsaved edits
 * ({@link runAction} skips this when {@code form.isDirty}).
 */
function hydrateUseFormValuesFromInertiaPage(
    page: Page | undefined,
    form: {
        setDefaults: (data: {
            values: Record<string, FormDataConvertible>;
        }) => void;
        setData: (data: {
            values: Record<string, FormDataConvertible>;
        }) => void;
        reset: () => void;
        clearErrors: () => void;
    },
    fields: FormFieldEntry[],
    entity: string,
): void {
    if (page?.props == null || typeof page.props !== 'object') {
        return;
    }
    const props = page.props as Record<string, unknown>;
    if (props.entity !== entity) {
        return;
    }
    const pageMode = props.mode;
    if (pageMode !== 'create' && pageMode !== 'edit') {
        return;
    }
    const raw = props.values;
    const values: Record<string, unknown> =
        typeof raw === 'object' && raw !== null && !Array.isArray(raw)
            ? (raw as Record<string, unknown>)
            : {};
    const nextValues = buildInitialValues(fields, values);
    const asFormValues = nextValues as Record<string, FormDataConvertible>;
    form.setDefaults({
        values: asFormValues,
    });
    form.setData({
        values: { ...asFormValues },
    });
    form.reset();
    form.clearErrors();
}

function isTabPanelLayout(value: unknown): value is FlatpackFormTabPanelLayout {
    if (typeof value !== 'object' || value === null || Array.isArray(value)) {
        return false;
    }
    const recordValue = value as Record<string, unknown>;
    if (typeof recordValue.id !== 'string' || recordValue.id.trim() === '') {
        return false;
    }
    if (
        typeof recordValue.label !== 'string' ||
        recordValue.label.trim() === ''
    ) {
        return false;
    }
    if (!Array.isArray(recordValue.field_ids)) {
        return false;
    }
    return true;
}

export function useFlatpackForm(props: FlatpackFormPageProps) {
    useFlatpackPage(props);
    const {
        entity,
        record,
        mode,
        schema,
        values = {},
        form_actions: formActions = [],
        sidebar_widgets: sidebarWidgets = {},
        sidebar_widgets_schema: sidebarWidgetsSchema = null,
    } = props;

    const fields = useMemo(
        () => normalizeFields(schema ?? undefined),
        [schema],
    );

    const tabPanels = useMemo((): FlatpackFormTabPanelLayout[] => {
        const raw = schema?.tab_panels;
        if (!Array.isArray(raw)) {
            return [];
        }
        return raw.filter(isTabPanelLayout);
    }, [schema]);

    const sidebarFieldIdSet = useMemo(() => {
        const raw = schema?.sidebar_field_ids;
        if (!Array.isArray(raw)) {
            return new Set<string>();
        }
        return new Set(
            raw.filter(
                (id): id is string => typeof id === 'string' && id !== '',
            ),
        );
    }, [schema?.sidebar_field_ids]);

    const mainFields = useMemo(
        () => fields.filter((f) => !sidebarFieldIdSet.has(f.id)),
        [fields, sidebarFieldIdSet],
    );

    const sidebarFieldsList = useMemo(
        () => fields.filter((f) => sidebarFieldIdSet.has(f.id)),
        [fields, sidebarFieldIdSet],
    );

    const mainTabPanels = useMemo((): FlatpackFormTabPanelLayout[] => {
        if (tabPanels.length === 0) {
            return [];
        }
        const mainIds = new Set(mainFields.map((f) => f.id));
        return tabPanels
            .map((p) => ({
                ...p,
                field_ids: p.field_ids.filter((id) => mainIds.has(id)),
            }))
            .filter((p) => p.field_ids.length > 0);
    }, [tabPanels, mainFields]);

    const fieldComponents = useMemo(
        () =>
            Object.fromEntries(
                fields.map(({ id, field }) => [id, loadField(field.type)]),
            ) as Record<string, ReturnType<typeof loadField>>,
        [fields],
    );
    const baselineValues = useMemo(
        () => buildInitialValues(fields, values),
        [fields, values],
    );

    const initialFormData = useMemo(
        () => ({
            values: baselineValues as Record<string, FormDataConvertible>,
        }),
        [baselineValues],
    );

    // @ts-expect-error Inertia generic recursion over dynamic record values.
    const form = useForm(initialFormData);
    const formRef = useRef(form);
    formRef.current = form;

    const { isDirty } = form;
    const fieldErrors = form.errors as Record<string, unknown>;
    const flatpackTopErrors = useMemo(() => {
        const errors = fieldErrorMessages(fieldErrors, 'flatpack');
        return [
            ...new Map(errors.map((error) => [error?.message, error])).values(),
        ];
    }, [fieldErrors]);

    const defaultIntent = useMemo(
        () => defaultFormSubmitIntent(formActions),
        [formActions],
    );

    const pendingSubmitIntentRef =
        useRef<FlatpackFormSubmitIntent>(defaultIntent);

    const prepareFormSubmit = useCallback(
        (row: FlatpackListHeaderAction & { action: string }) => {
            pendingSubmitIntentRef.current = { id: row.id, action: row.action };
        },
        [],
    );

    useEffect(() => {
        pendingSubmitIntentRef.current = defaultIntent;
    }, [defaultIntent]);

    const [pendingConfirm, setPendingConfirm] =
        useState<FlatpackFormPendingConfirm | null>(null);
    const [actionProcessing, setActionProcessing] = useState(false);

    useEffect(() => {
        const f = formRef.current;
        const nextDefaults = {
            values: baselineValues as Record<string, FormDataConvertible>,
        };
        if (f.isDirty) {
            // Keep local unsaved edits across failed submits (including embedded table drafts).
            f.setDefaults(nextDefaults);
            return;
        }
        f.setDefaults(nextDefaults);
        f.reset();
        f.clearErrors();
    }, [baselineValues]);

    const { mergeFieldChange } = useFormFieldPresets({
        fields,
        baselineValues,
    });

    const setFieldValue = useCallback(
        (field: FormFieldProps, fieldId: string, nextValue: unknown) => {
            const nextValues = mergeFieldChange(
                field,
                fieldId,
                nextValue,
                form.data.values as Record<string, unknown>,
            );

            form.setData({
                ...form.data,
                values: nextValues as Record<string, FormDataConvertible>,
            });
            form.clearErrors();
        },
        [form, mergeFieldChange],
    );

    const runSubmit = useCallback(() => {
        const validationErrors = clientValidationErrors(
            fields,
            form.data.values,
        );
        const submitUrl = route('flatpack.entities.form.submit', { entity });
        if (Object.keys(validationErrors).length > 0) {
            form.clearErrors();
            form.setError(validationErrors);
            toast.error(
                firstErrorMessage(validationErrors) ?? 'Please review errors',
            );
            return;
        }

        form.clearErrors();

        const intent = pendingSubmitIntentRef.current;
        if (intent.id === '' || intent.action.trim() === '') {
            toast.error('No submit action is configured for this form');
            return;
        }

        form.transform((data) => {
            const payload: Record<string, unknown> = {
                ...data,
                action: intent.action,
            };
            if (intent.id !== '') {
                payload.form_action_id = intent.id;
            }
            if (mode === 'edit' && record != null && record !== '') {
                payload.record = record;
            }
            return payload;
        });

        setActionProcessing(true);
        const options = {
            preserveScroll: true,
            preserveState: 'errors' as const,
            onSuccess: (page: Page) => {
                hydrateUseFormValuesFromInertiaPage(page, form, fields, entity);
                const submittedAction = formActions.find(
                    (a) => a.id === intent.id,
                );
                if (submittedAction?.success_message) {
                    toast.success(submittedAction.success_message);
                }
            },
            onError: (errors: Record<string, unknown>) => {
                toast.error(
                    firstErrorMessage(errors) ?? 'Form submission failed',
                );
            },
            onFinish: () => {
                setActionProcessing(false);
            },
        };

        bypassNextInertiaLeaveGuard();
        form.post(submitUrl, options);
    }, [entity, fields, form, formActions, mode, record]);

    const runAction = useCallback(
        (config: FlatpackListHeaderAction & { action: string }) => {
            if (config.handler_missing === true) {
                toast.error(
                    'This action is not registered under flatpack.actions in config.',
                );
                return;
            }
            if (config.submit === true) {
                prepareFormSubmit(config);
                runSubmit();
                return;
            }
            if (mode !== 'edit' || record == null || record === '') {
                toast.error('This action requires an existing record');
                return;
            }
            setActionProcessing(true);
            bypassNextInertiaLeaveGuard();
            router.post(
                route('flatpack.entities.row-action', {
                    entity,
                    record: String(record),
                }),
                { action: config.action },
                {
                    preserveState: false,
                    preserveScroll: true,
                    onSuccess: (page: Page) => {
                        if (!form.isDirty) {
                            hydrateUseFormValuesFromInertiaPage(
                                page,
                                form,
                                fields,
                                entity,
                            );
                        }
                        if (config.success_message) {
                            toast.success(config.success_message);
                        }
                    },
                    onError: (errors: Record<string, unknown>) => {
                        toast.error(
                            firstErrorMessage(errors) ?? 'Action failed',
                        );
                    },
                    onFinish: () => {
                        setActionProcessing(false);
                    },
                },
            );
        },
        [entity, fields, form, mode, prepareFormSubmit, record, runSubmit],
    );

    const handleSubmit = useCallback(
        (event: FormEvent<HTMLFormElement>) => {
            event.preventDefault();
            runSubmit();
        },
        [runSubmit],
    );

    return {
        form,
        isDirty,
        fields,
        mainFields,
        sidebarFields: sidebarFieldsList,
        tabPanels: mainTabPanels,
        fieldComponents,
        fieldErrors,
        flatpackTopErrors,
        formActions,
        prepareFormSubmit,
        pendingConfirm,
        setPendingConfirm,
        setFieldValue,
        handleSubmit,
        runSubmit,
        runAction,
        formProcessing: form.processing || actionProcessing,
        sidebarWidgets,
        sidebarWidgetsSchema,
    };
}
