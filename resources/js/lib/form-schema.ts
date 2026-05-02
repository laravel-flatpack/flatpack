import { SUPPORTED_FORM_FIELD_TYPES } from '@/lib/form-schema-contract';
import { tableFieldErrorState } from '@/lib/form-table-errors';
import type { FormFieldProps, FormFieldType } from '@/types/form-fields';
import type { SchemaFieldRenderEntry } from '@/types/schema-fields-renderer';

export type FormFieldEntry = {
    id: string;
    field: FormFieldProps;
};

export type FlatpackFormTabPanelLayout = {
    id: string;
    label: string;
    icon?: string;
    field_ids: string[];
};

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

/**
 * Maps raw YAML / PHP `type` strings to canonical {@link FormFieldType}
 * (same rules as root form fields). Used for nested definitions such as repeater items.
 */
export function normalizeYamlFieldType(
    value: unknown,
): FormFieldType | undefined {
    if (value === 'date') {
        return 'date-picker';
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
        case 'file-upload':
            return field.multiple ? [] : null;
        case 'repeater': {
            const minRaw = (field as { minItems?: unknown }).minItems;
            const min =
                typeof minRaw === 'number' && minRaw >= 0
                    ? Math.floor(minRaw)
                    : 0;
            return Array.from({ length: min }, () => ({}));
        }
        default:
            return undefined;
    }
}

function canonicalDateSegment(value: unknown): string | null {
    if (value instanceof Date) {
        if (Number.isNaN(value.getTime())) {
            return null;
        }
        const year = value.getFullYear();
        const month = String(value.getMonth() + 1).padStart(2, '0');
        const day = String(value.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    if (typeof value !== 'string') {
        return null;
    }
    const trimmed = value.trim();
    if (trimmed === '') {
        return '';
    }
    const match = /^(\d{4}-\d{2}-\d{2})/.exec(trimmed);
    return match ? match[1] : trimmed;
}

function canonicalInitialFieldValue(
    field: FormFieldProps,
    value: unknown,
): unknown {
    if (field.type === 'date-picker') {
        const date = canonicalDateSegment(value);
        return date ?? value;
    }
    return value;
}

export function normalizeFields(
    schema?: Record<string, unknown> | null,
): FormFieldEntry[] {
    if (!isRecord(schema)) {
        return [];
    }
    const rawFields = schema.fields;
    if (!isRecord(rawFields)) {
        return [];
    }

    return Object.entries(rawFields).flatMap(([fieldId, fieldDefinition]) => {
        if (!isRecord(fieldDefinition)) {
            return [];
        }
        const type = normalizeYamlFieldType(fieldDefinition.type);
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
            nextValues[id] = canonicalInitialFieldValue(field, values[id]);
            continue;
        }

        const defaultValue = defaultValueForField(field);
        if (defaultValue !== undefined) {
            nextValues[id] = canonicalInitialFieldValue(field, defaultValue);
        }
    }

    return nextValues;
}

function fieldErrorMessagesForKey(
    errors: Record<string, unknown>,
    key: string,
): Array<{ message: string }> {
    const error = errors[key];
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

/**
 * Maps Laravel / Inertia error keys for a logical field id (`title` or `values.title`).
 */
export function fieldErrorMessages(
    errors: Record<string, unknown>,
    fieldId: string,
): Array<{ message: string }> {
    const seen = new Set<string>();
    const out: Array<{ message: string }> = [];
    for (const key of [`values.${fieldId}`, fieldId]) {
        for (const row of fieldErrorMessagesForKey(errors, key)) {
            if (!seen.has(row.message)) {
                seen.add(row.message);
                out.push(row);
            }
        }
    }
    return out;
}

/** Stable fingerprint of which field-level validation keys are set (excludes `flatpack` top errors). */
export function validationErrorsFingerprint(
    errors: Record<string, unknown>,
): string {
    return Object.keys(errors)
        .filter((k) => k !== 'flatpack')
        .sort()
        .join('\0');
}

export function fieldHasValidationError(
    errors: Record<string, unknown>,
    fieldId: string,
    field: FormFieldProps,
): boolean {
    if (fieldErrorMessages(errors, fieldId).length > 0) {
        return true;
    }
    if (field.type === 'table') {
        return tableFieldErrorState(errors, fieldId) !== null;
    }
    return false;
}

export function firstVisibleFieldEntryWithValidationError(
    orderedEntries: SchemaFieldRenderEntry[],
    fieldErrors: Record<string, unknown>,
): SchemaFieldRenderEntry | undefined {
    return orderedEntries.find(
        (e) =>
            e.hidden !== true &&
            fieldHasValidationError(fieldErrors, e.id, e.field),
    );
}
