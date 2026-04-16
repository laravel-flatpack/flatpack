import { Head, router } from '@inertiajs/react';
import { Suspense, useCallback, useEffect, useMemo, useState } from 'react';
import type { DateRange } from 'react-day-picker';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { FieldError } from '@/components/ui/field';
import FlatpackLayout from '@/layouts/flatpack-layout';
import { localDateSegment } from '@/lib/data-table-utils';
import { loadField } from '@/lib/form';
import { mapFormFieldPropsToComponentProps } from '@/lib/form-field-props';
import { route } from '@/lib/route';
import type { FormFieldProps, FormFieldType } from '@/types/form-fields';
import type { FlatpackFormPageProps } from '@/types/pages/flatpack';

const supportedFormFieldTypes = [
    'text',
    'textarea',
    'select',
    'combobox',
    'date-picker',
    'date-range-picker',
    'time-picker',
    'checkbox',
    'switch',
    'rich-text',
    'block-editor',
    'table',
] as const satisfies readonly FormFieldType[];

type FieldEntry = {
    id: string;
    field: FormFieldProps;
};

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function isFormFieldType(value: unknown): value is FormFieldType {
    return (
        typeof value === 'string' &&
        supportedFormFieldTypes.includes(value as FormFieldType)
    );
}

function normalizeFields(
    schema?: Record<string, unknown> | null,
): FieldEntry[] {
    const rawFields = schema?.fields;
    if (!isRecord(rawFields)) {
        return [];
    }

    return Object.entries(rawFields).flatMap(([fieldId, fieldDefinition]) => {
        if (
            !isRecord(fieldDefinition) ||
            !isFormFieldType(fieldDefinition.type)
        ) {
            return [];
        }

        const id = String(fieldDefinition.id ?? fieldId).trim();
        if (id === '') {
            return [];
        }

        return [
            {
                id,
                field: {
                    ...fieldDefinition,
                    type: fieldDefinition.type,
                } as FormFieldProps,
            },
        ];
    });
}

function defaultValueForField(field: FormFieldProps): unknown {
    const fieldRecord = field as Record<string, unknown>;
    if (fieldRecord.value !== undefined) {
        return fieldRecord.value;
    }

    switch (field.type) {
        case 'checkbox':
        case 'switch':
            return field.defaultChecked ?? false;
        case 'table':
            return field.data ?? [];
        case 'select':
            return null;
        case 'combobox':
            return field.multiple ? [] : null;
        default:
            return undefined;
    }
}

function buildInitialValues(
    fields: FieldEntry[],
    values: Record<string, unknown>,
): Record<string, unknown> {
    const nextValues: Record<string, unknown> = {};

    for (const { id, field } of fields) {
        if (Object.hasOwn(values, id)) {
            nextValues[id] = values[id];
            continue;
        }

        const defaultValue = defaultValueForField(field);
        if (defaultValue !== undefined) {
            nextValues[id] = defaultValue;
        }
    }

    return nextValues;
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

function fieldErrorMessages(
    errors: Record<string, unknown>,
    fieldId: string,
): Array<{ message: string }> {
    const error = errors[fieldId];
    if (typeof error === 'string' && error.trim() !== '') {
        return [{ message: error }];
    }

    if (Array.isArray(error)) {
        return error
            .filter(
                (item): item is string =>
                    typeof item === 'string' && item.trim() !== '',
            )
            .map((message) => ({ message }));
    }

    return [];
}

export default function FlatpackFormPage({
    entity,
    name,
    record,
    mode,
    schema,
    values = {},
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
    const [fieldValues, setFieldValues] =
        useState<Record<string, unknown>>(initialValues);
    const [fieldErrors, setFieldErrors] = useState<Record<string, unknown>>({});
    const [submitting, setSubmitting] = useState(false);
    const displayName = name ?? entity;
    const pageTitle =
        mode === 'create' ? `Create ${displayName}` : `Edit ${displayName}`;
    const submitLabel = mode === 'create' ? 'Create' : 'Save';

    useEffect(() => {
        setFieldValues(initialValues);
        setFieldErrors({});
        setSubmitting(false);
    }, [initialValues]);

    const setFieldValue = useCallback(
        (field: FormFieldProps, fieldId: string, nextValue: unknown) => {
            setFieldValues((current) => ({
                ...current,
                [fieldId]: serializeFieldValue(field, nextValue),
            }));
            setFieldErrors((current) => {
                if (!Object.hasOwn(current, fieldId)) {
                    return current;
                }

                const next = { ...current };
                delete next[fieldId];
                return next;
            });
        },
        [],
    );

    const handleSubmit = useCallback(
        (event: React.FormEvent<HTMLFormElement>) => {
            event.preventDefault();

            const submitUrl =
                mode === 'create'
                    ? route('flatpack.entities.store', { entity })
                    : route('flatpack.entities.save', {
                          entity,
                          record: record ?? '',
                      });

            setSubmitting(true);
            setFieldErrors({});

            const options = {
                preserveScroll: true,
                onSuccess: () => {
                    setSubmitting(false);
                    setFieldErrors({});
                },
                onError: (errors: Record<string, unknown>) => {
                    setSubmitting(false);
                    setFieldErrors(errors);
                    toast.error(
                        firstErrorMessage(errors) ?? 'Form save failed',
                    );
                },
            };

            if (mode === 'create') {
                router.post(
                    submitUrl,
                    { values: fieldValues as never },
                    options,
                );
                return;
            }

            router.patch(submitUrl, { values: fieldValues as never }, options);
        },
        [entity, fieldValues, mode, record],
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
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {displayName}
                    </h1>
                    <p className="text-muted-foreground">
                        {mode === 'create'
                            ? `Create a new ${displayName}.`
                            : `Edit ${displayName} (${record ?? 'unknown'}).`}
                    </p>
                </div>

                <form className="flex flex-col gap-6" onSubmit={handleSubmit}>
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
                                ...componentValueProps(field, fieldValues[id]),
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

                    <div className="flex items-center justify-end gap-2">
                        <Button
                            type="submit"
                            size="lg"
                            disabled={submitting || fields.length === 0}
                        >
                            {submitting ? `${submitLabel}...` : submitLabel}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

FlatpackFormPage.layout = (page: React.ReactElement<FlatpackFormPageProps>) => (
    <FlatpackLayout>{page}</FlatpackLayout>
);
