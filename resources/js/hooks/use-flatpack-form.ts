import type { FormDataConvertible } from '@inertiajs/core';
import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import {
    useCallback,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import { toast } from 'sonner';
import { loadField } from '@/lib/form';
import { firstErrorMessage } from '@/lib/form-errors';
import { serializeFieldValue } from '@/lib/form-page-field-values';
import {
    buildInitialValues,
    fieldErrorMessages,
    normalizeFields,
} from '@/lib/form-schema';
import { clientValidationErrors } from '@/lib/form-validation';
import { route } from '@/lib/route';
import { useFormFieldPresets } from '@/hooks/use-form-field-presets';
import type { FormFieldProps } from '@/types/form-fields';
import type {
    FlatpackFormPageProps,
    FlatpackListHeaderAction,
} from '@/types/pages/flatpack';

export type FlatpackFormPendingConfirm =
    | { kind: 'save' }
    | {
          kind: 'named';
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
    const baselineSignature = useMemo(
        () => JSON.stringify(buildInitialValues(fields, values)),
        [fields, values],
    );

    const initialFormData = useMemo(
        () => ({
            values: JSON.parse(baselineSignature) as Record<
                string,
                FormDataConvertible
            >,
        }),
        [baselineSignature],
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
    const saveActionConfig = useMemo(
        () =>
            formActions.find(
                (a): a is FlatpackListHeaderAction & { action: 'save' } =>
                    'action' in a && a.action === 'save',
            ),
        [formActions],
    );
    const [pendingConfirm, setPendingConfirm] =
        useState<FlatpackFormPendingConfirm | null>(null);

    useEffect(() => {
        const nextValues = JSON.parse(baselineSignature) as Record<
            string,
            FormDataConvertible
        >;
        const f = formRef.current;
        f.setDefaults({ values: nextValues });
        f.reset();
        f.clearErrors();
    }, [baselineSignature]);

    const { mergeFieldChange } = useFormFieldPresets({
        fields,
        baselineSignature,
    });

    const setFieldValue = useCallback(
        (field: FormFieldProps, fieldId: string, nextValue: unknown) => {
            const nextValues = mergeFieldChange(
                field,
                fieldId,
                serializeFieldValue(field, nextValue),
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

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                form.clearErrors();
                form.setDefaults({
                    values: form.data.values as Record<
                        string,
                        FormDataConvertible
                    >,
                });
                form.reset();
                if (saveActionConfig?.success_message) {
                    toast.success(saveActionConfig.success_message);
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
    }, [entity, fields, form, mode, record, saveActionConfig]);

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
        saveActionConfig,
        pendingConfirm,
        setPendingConfirm,
        setFieldValue,
        handleSubmit,
        runSubmit,
        executeNamedAction,
    };
}
