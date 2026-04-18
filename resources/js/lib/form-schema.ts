import { SUPPORTED_FORM_FIELD_TYPES } from '@/lib/form-schema-contract';
import type { FormFieldProps, FormFieldType } from '@/types/form-fields';

export type FormFieldEntry = {
    id: string;
    field: FormFieldProps;
};

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function normalizeFieldType(value: unknown): FormFieldType | undefined {
    if (value === 'date') {
        return 'date-picker';
    }
    if (value === 'relation') {
        return 'combobox';
    }
    return typeof value === 'string' &&
        SUPPORTED_FORM_FIELD_TYPES.includes(value as FormFieldType)
        ? (value as FormFieldType)
        : undefined;
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

export function normalizeFields(
    schema?: Record<string, unknown> | null,
): FormFieldEntry[] {
    const rawFields = schema?.fields;
    if (!isRecord(rawFields)) {
        return [];
    }

    return Object.entries(rawFields).flatMap(([fieldId, fieldDefinition]) => {
        if (!isRecord(fieldDefinition)) {
            return [];
        }
        const type = normalizeFieldType(fieldDefinition.type);
        if (type === undefined) {
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
                    type,
                } as FormFieldProps,
            },
        ];
    });
}

export function buildInitialValues(
    fields: FormFieldEntry[],
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

export function fieldErrorMessages(
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
