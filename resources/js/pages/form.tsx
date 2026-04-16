import type { FormDataConvertible } from '@inertiajs/core';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Loader2Icon } from 'lucide-react';
import { Suspense, useCallback, useEffect, useMemo } from 'react';
import type { DateRange } from 'react-day-picker';
import { toast } from 'sonner';
import { LucideIconByName } from '@/components/icons';
import { Button } from '@/components/ui/button';
import { FieldError } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import FlatpackLayout from '@/layouts/flatpack-layout';
import { localDateSegment } from '@/lib/data-table-utils';
import { loadField } from '@/lib/form';
import { mapFormFieldPropsToComponentProps } from '@/lib/form-field-props';
import {
    buildInitialValues,
    fieldErrorMessages,
    normalizeFields,
} from '@/lib/form-schema';
import { clientValidationErrors, fieldIsRequired } from '@/lib/form-validation';
import { route } from '@/lib/route';
import { cn } from '@/lib/utils';
import type { FormFieldProps } from '@/types/form-fields';
import type { FlatpackFormPageProps } from '@/types/pages/flatpack';

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function firstErrorMessage(
    errors: Record<string, unknown>,
): string | undefined {
    for (const value of Object.values(errors)) {
        if (typeof value === 'string' && value.trim() !== '') {
            return value;
        }

        if (Array.isArray(value)) {
            const first = value.find(
                (item): item is string =>
                    typeof item === 'string' && item.trim() !== '',
            );
            if (first !== undefined) {
                return first;
            }
        }
    }

    return undefined;
}

function parseLocalDate(value: unknown): Date | undefined {
    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? undefined : value;
    }

    if (typeof value !== 'string') {
        return undefined;
    }

    const trimmed = value.trim();
    if (trimmed === '') {
        return undefined;
    }

    const dateOnly = /^(\d{4})-(\d{2})-(\d{2})$/.exec(trimmed);
    if (dateOnly) {
        const [, year, month, day] = dateOnly;
        return new Date(Number(year), Number(month) - 1, Number(day));
    }

    const parsed = new Date(trimmed);
    return Number.isNaN(parsed.getTime()) ? undefined : parsed;
}

function parseDateRangeValue(value: unknown): DateRange | undefined {
    if (!isRecord(value)) {
        return undefined;
    }

    const from = parseLocalDate(value.from);
    const to = parseLocalDate(value.to);
    if (from === undefined && to === undefined) {
        return undefined;
    }

    return { from, to };
}

function parseTimePickerValue(
    value: unknown,
): { date?: Date; time?: string } | undefined {
    if (!isRecord(value)) {
        return undefined;
    }

    const date = parseLocalDate(value.date);
    const time = typeof value.time === 'string' ? value.time : undefined;
    if (date === undefined && time === undefined) {
        return undefined;
    }

    return { date, time };
}

function serializeFieldValue(field: FormFieldProps, value: unknown): unknown {
    switch (field.type) {
        case 'date-picker': {
            const nextDate = value instanceof Date ? value : undefined;
            return nextDate === undefined ? null : localDateSegment(nextDate);
        }
        case 'date-range-picker': {
            if (!isRecord(value)) {
                return null;
            }

            const from =
                value.from instanceof Date
                    ? localDateSegment(value.from)
                    : null;
            const to =
                value.to instanceof Date ? localDateSegment(value.to) : null;
            return from === null && to === null ? null : { from, to };
        }
        case 'time-picker': {
            if (!isRecord(value)) {
                return null;
            }

            const date =
                value.date instanceof Date
                    ? localDateSegment(value.date)
                    : null;
            const time = typeof value.time === 'string' ? value.time : '';
            return { date, time };
        }
        default:
            return value;
    }
}

function componentValueProps(
    field: FormFieldProps,
    value: unknown,
): Record<string, unknown> {
    switch (field.type) {
        case 'text':
            return { value: value == null ? '' : String(value) };
        case 'textarea':
            return { value: value == null ? '' : String(value) };
        case 'select':
        case 'combobox':
            return { value };
        case 'checkbox':
        case 'switch':
            return { checked: value === true };
        case 'date-picker':
            return { value: parseLocalDate(value) };
        case 'date-range-picker':
            return { value: parseDateRangeValue(value) };
        case 'time-picker':
            return { value: parseTimePickerValue(value) };
        case 'rich-text':
        case 'block-editor':
            return {
                initialValue: Array.isArray(value) ? value : undefined,
            };
        case 'table':
            return {
                data: Array.isArray(value) ? value : (field.data ?? []),
            };
        default:
            return {};
    }
}

function relationRemoteProps(
    field: FormFieldProps,
    fieldId: string,
    entity: string,
): Record<string, unknown> {
    if (field.type !== 'combobox') {
        return {};
    }

    const relationField = field as FormFieldProps & {
        relation?: unknown;
        remote?: unknown;
    };
    if (
        typeof relationField.relation !== 'string' ||
        relationField.relation.trim() === ''
    ) {
        return {};
    }

    return {
        remote: relationField.remote === true,
        remoteEndpoint: route('flatpack.entities.relation-options', { entity }),
        remoteFieldId: fieldId,
    };
}

export default function FlatpackFormPage({
    entity,
    name,
    record,
    mode,
    schema,
    values = {},
    form_actions: formActions = [],
}: FlatpackFormPageProps) {
    const fields = useMemo(() => normalizeFields(schema), [schema]);
    const initialValues = useMemo(
        () => buildInitialValues(fields, values),
        [fields, values],
    );
    const fieldComponents = useMemo(
        () =>
            Object.fromEntries(
                fields.map(({ id, field }) => [id, loadField(field.type)]),
            ) as Record<string, ReturnType<typeof loadField>>,
        [fields],
    );
    const initialFormData = useMemo(
        () => ({
            values: initialValues as Record<string, FormDataConvertible>,
        }),
        [initialValues],
    );
    // @ts-expect-error Inertia generic recursion over dynamic record values.
    const form = useForm(initialFormData);
    const fieldErrors = form.errors as Record<string, unknown>;
    const displayName = name ?? entity;
    const pageTitle =
        mode === 'create' ? `Create ${displayName}` : `Edit ${displayName}`;
    const formId = `flatpack-form-${entity}-${record ?? 'new'}`;

    useEffect(() => {
        form.setData(
            'values',
            initialValues as Record<string, FormDataConvertible>,
        );
        form.clearErrors();
    }, [form.clearErrors, form.setData, initialValues]);

    const setFieldValue = useCallback(
        (field: FormFieldProps, fieldId: string, nextValue: unknown) => {
            form.setData({
                ...form.data,
                values: {
                    ...form.data.values,
                    [fieldId]: serializeFieldValue(
                        field,
                        nextValue,
                    ) as FormDataConvertible,
                },
            });
            form.clearErrors();
        },
        [form],
    );

    const handleSubmit = useCallback(
        (event: React.FormEvent<HTMLFormElement>) => {
            event.preventDefault();
            const validationErrors = clientValidationErrors(
                fields,
                form.data.values,
            );
            if (Object.keys(validationErrors).length > 0) {
                form.clearErrors();
                form.setError(validationErrors);
                toast.error(
                    firstErrorMessage(validationErrors) ??
                        'Please review errors',
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
                },
                onError: (errors: Record<string, unknown>) => {
                    toast.error(
                        firstErrorMessage(errors) ?? 'Form save failed',
                    );
                },
            };

            if (mode === 'create') {
                form.post(submitUrl, options);
                return;
            }

            form.patch(submitUrl, options);
        },
        [entity, fields, form, mode, record],
    );

    const handleNamedAction = useCallback(
        async (action: string) => {
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
                        onSuccess: () => resolve(),
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

    const noFieldsMessage =
        fields.length === 0
            ? 'Define fields in form.yaml to render this form.'
            : null;

    return (
        <>
            <Head title={pageTitle} />
            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-2">
                    <div className="mb-2 flex w-full flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
                        <div className="min-w-0 flex-1">
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {displayName}
                            </h1>
                            <p className="text-muted-foreground">
                                {mode === 'create'
                                    ? `Create a new ${displayName}.`
                                    : `Edit ${displayName} (${record ?? 'unknown'}).`}
                            </p>
                        </div>
                        {formActions.length > 0 ? (
                            <div className="flex shrink-0 flex-wrap items-center justify-start gap-2 sm:justify-end">
                                {formActions.map((action) =>
                                    'href' in action ? (
                                        <Button
                                            key={action.id}
                                            asChild
                                            size="lg"
                                            variant={
                                                action.variant ?? 'outline'
                                            }
                                        >
                                            <Link
                                                href={action.href}
                                                className={cn(
                                                    action.icon &&
                                                        'inline-flex items-center gap-1.5',
                                                )}
                                            >
                                                {action.icon ? (
                                                    <LucideIconByName
                                                        name={action.icon}
                                                    />
                                                ) : null}
                                                {action.label}
                                            </Link>
                                        </Button>
                                    ) : action.action === 'save' ? (
                                        <Button
                                            key={action.id}
                                            type="submit"
                                            form={formId}
                                            size="lg"
                                            variant={
                                                action.variant ?? 'outline'
                                            }
                                            disabled={
                                                form.processing ||
                                                fields.length === 0
                                            }
                                            className={cn(
                                                action.icon &&
                                                    'inline-flex items-center gap-1.5',
                                            )}
                                        >
                                            {action.icon && (
                                                <LucideIconByName
                                                    name={action.icon}
                                                />
                                            )}
                                            {form.processing && (
                                                <Spinner className="size-4" />
                                            )}
                                            {action.label}
                                        </Button>
                                    ) : (
                                        <Button
                                            key={action.id}
                                            type="button"
                                            size="lg"
                                            variant={
                                                action.variant ?? 'outline'
                                            }
                                            disabled={
                                                form.processing ||
                                                record == null ||
                                                record === ''
                                            }
                                            className={cn(
                                                action.icon &&
                                                    'inline-flex items-center gap-1.5',
                                            )}
                                            onClick={() =>
                                                handleNamedAction(action.action)
                                            }
                                        >
                                            {action.icon ? (
                                                <LucideIconByName
                                                    name={action.icon}
                                                />
                                            ) : null}
                                            {action.label}
                                        </Button>
                                    ),
                                )}
                            </div>
                        ) : null}
                    </div>
                </div>

                <form
                    id={formId}
                    className="flex flex-col gap-6"
                    noValidate
                    onSubmit={handleSubmit}
                >
                    <FieldError
                        errors={fieldErrorMessages(fieldErrors, 'flatpack')}
                    />

                    {noFieldsMessage ? (
                        <p className="text-sm text-muted-foreground">
                            {noFieldsMessage}
                        </p>
                    ) : (
                        fields.map(({ id, field }) => {
                            const FieldComponent = fieldComponents[id];
                            const componentProps = {
                                ...mapFormFieldPropsToComponentProps(field, {
                                    fieldId: id,
                                    onValueChange: (nextValue: unknown) =>
                                        setFieldValue(field, id, nextValue),
                                }),
                                ...componentValueProps(
                                    field,
                                    form.data.values[id],
                                ),
                                ...relationRemoteProps(field, id, entity),
                                required: fieldIsRequired(field),
                                invalid:
                                    fieldErrorMessages(fieldErrors, id).length >
                                    0,
                            };

                            return (
                                <div
                                    key={`${id}:${mode}:${record ?? 'new'}`}
                                    className="space-y-2"
                                >
                                    <Suspense
                                        fallback={
                                            <div className="h-9 animate-pulse rounded-3xl bg-muted" />
                                        }
                                    >
                                        <FieldComponent {...componentProps} />
                                    </Suspense>
                                    <FieldError
                                        errors={fieldErrorMessages(
                                            fieldErrors,
                                            id,
                                        )}
                                    />
                                </div>
                            );
                        })
                    )}
                </form>
            </div>
        </>
    );
}

FlatpackFormPage.layout = (page: React.ReactElement<FlatpackFormPageProps>) => (
    <FlatpackLayout>{page}</FlatpackLayout>
);
