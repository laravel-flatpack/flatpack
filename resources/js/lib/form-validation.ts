import type { FormFieldProps } from '@/types/form-fields';

export type FormValidationFieldEntry = {
    id: string;
    field: FormFieldProps;
};

export function fieldIsRequired(field: FormFieldProps): boolean {
    const raw = field as Record<string, unknown>;
    return raw.required === true;
}

function isValueEmpty(value: unknown): boolean {
    if (value == null) {
        return true;
    }

    if (typeof value === 'string') {
        return value.trim() === '';
    }

    if (Array.isArray(value)) {
        return value.length === 0;
    }

    if (typeof value === 'object') {
        if (value instanceof Date) {
            return Number.isNaN(value.getTime());
        }

        if ('value' in value) {
            return isValueEmpty((value as { value?: unknown }).value);
        }

        return Object.keys(value as Record<string, unknown>).length === 0;
    }

    return false;
}

export function clientValidationErrors(
    fields: FormValidationFieldEntry[],
    values: Record<string, unknown>,
): Record<string, string> {
    const errors: Record<string, string> = {};

    for (const { id, field } of fields) {
        if (!fieldIsRequired(field)) {
            continue;
        }

        if (!isValueEmpty(values[id])) {
            continue;
        }

        const label = field.label.trim() !== '' ? field.label : id;
        errors[id] = `${label} is required.`;
    }

    return errors;
}
