import type { FormDataConvertible } from '@inertiajs/core';
import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import { useFormFieldPresets } from '@/hooks/use-form-field-presets';
import { loadField } from '@/lib/form';
import { firstErrorMessage } from '@/lib/form-errors';
import {
    buildInitialValues,
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

/** Header action waiting for confirm dialog; branch on {@code config.action === 'save'} vs named action. */
export type FlatpackFormPendingConfirm = {
    config: FlatpackListHeaderAction & { action: string };
};

export function useFlatpackForm({
    entity,
    record,
    mode,
    schema,
    values = {},
    form_actions: formActions = [],
}: FlatpackFormPageProps) {
    const fields = useMemo(() => normalizeFields(schema), [schema]);
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
    const defaultSaveActionId = useMemo(
        () =>
            formActions.find(
                (a): a is FlatpackListHeaderAction & { action: 'save' } =>
                    'action' in a && a.action === 'save',
            )?.id ?? '',
        [formActions],
    );

    const pendingSaveActionIdRef = useRef(defaultSaveActionId);

    const prepareSaveSubmit = useCallback(
        (action: FlatpackListHeaderAction & { action: 'save' }) => {
            pendingSaveActionIdRef.current = action.id;
        },
        [],
    );

    useEffect(() => {
        pendingSaveActionIdRef.current = defaultSaveActionId;
    }, [defaultSaveActionId]);

    const [pendingConfirm, setPendingConfirm] =
        useState<FlatpackFormPendingConfirm | null>(null);

    useEffect(() => {
        const f = formRef.current;
        f.setDefaults({
            values: baselineValues as Record<string, FormDataConvertible>,
        });
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
        if (Object.keys(validationErrors).length > 0) {
            form.clearErrors();
            form.setError(validationErrors);
            const submitUrl =
                mode === 'create'
                    ? route('flatpack.entities.store', { entity })
                    : route('flatpack.entities.save', {
                          entity,
                          record: record ?? '',
                      });
            toast.error(
                firstErrorMessage(validationErrors) ?? 'Please review errors',
            );
            return;
        }

        const submitUrl =
            mode === 'create'
                ? route('flatpack.entities.store', { entity })
                : route('flatpack.entities.save', {
                      entity,
                      record: record ?? '',
                  });

        form.clearErrors();

        const submittedActionId = pendingSaveActionIdRef.current;

        form.transform((data) => ({
            ...data,
            form_action_id: submittedActionId,
        }));

        const options = {
            preserveScroll: true,
            /** Default PATCH merges {@code preserveState: true}, which keeps stale props after 303 → same edit URL. */
            preserveState: false,
            onSuccess: () => {
                form.clearErrors();
                form.setDefaults({
                    values: form.data.values as Record<
                        string,
                        FormDataConvertible
                    >,
                });
                form.reset();
                const submittedAction = formActions.find(
                    (a) => a.id === submittedActionId,
                );
                if (submittedAction?.success_message) {
                    toast.success(submittedAction.success_message);
                }
            },
            onError: (errors: Record<string, unknown>) => {
                toast.error(firstErrorMessage(errors) ?? 'Form save failed');
            },
        };

        if (mode === 'create') {
            form.post(submitUrl, options);
            return;
        }

        form.patch(submitUrl, options);
    }, [entity, fields, form, formActions, mode, record]);

    const handleSubmit = useCallback(
        (event: FormEvent<HTMLFormElement>) => {
            event.preventDefault();
            runSubmit();
        },
        [runSubmit],
    );

    const executeNamedAction = useCallback(
        async (config: FlatpackListHeaderAction & { action: string }) => {
            const { action } = config;
            if (action === 'save') {
                return;
            }
            if (record == null || record === '') {
                return;
            }

            await new Promise<void>((resolve, reject) => {
                router.post(
                    route('flatpack.entities.row-action', {
                        entity,
                        record,
                    }),
                    { action },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            resolve();
                            if (config.success_message) {
                                toast.success(config.success_message);
                            }
                        },
                        onError: (errors) => {
                            const message =
                                firstErrorMessage(errors) ??
                                'Form action failed';
                            toast.error(message);
                            reject(new Error(message));
                        },
                    },
                );
            });
        },
        [entity, record],
    );

    return {
        form,
        isDirty,
        fields,
        fieldComponents,
        fieldErrors,
        flatpackTopErrors,
        formActions,
        prepareSaveSubmit,
        pendingConfirm,
        setPendingConfirm,
        setFieldValue,
        handleSubmit,
        runSubmit,
        executeNamedAction,
    };
}
