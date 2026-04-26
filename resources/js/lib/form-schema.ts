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

function normalizeFieldType(value: unknown): FormFieldType | undefined {
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

/**
 * When raw schema still has {@code tabs} (e.g. JSON debug), merge nested fields into
 * {@code fields} and build {@code tab_panels} the same way as the PHP normalizer.
 *
 * Top-level {@code fields} and {@code tabs.*.fields} are combined: root keys are applied first, then tab
 * keys (overwriting on duplicate ids). The form UI renders fields that are not listed in any
 * {@code tab_panels.field_ids} above the tab strip.
 */
export function mergeFormTabsIntoSchemaFields(
    schema: Record<string, unknown>,
): Record<string, unknown> {
    const tabs = schema.tabs;
    if (!isRecord(tabs)) {
        return schema;
    }

    const merged: Record<string, unknown> = {};
    if (isRecord(schema.fields)) {
        Object.assign(merged, schema.fields);
    }

    const tab_panels: FlatpackFormTabPanelLayout[] = [];

    for (const [tabId, panel] of Object.entries(tabs)) {
        if (!isRecord(panel)) {
            continue;
        }
        const label = typeof panel.label === 'string' ? panel.label.trim() : '';
        if (label === '') {
            continue;
        }
        const iconRaw = panel.icon;
        const icon =
            typeof iconRaw === 'string' && iconRaw.trim() !== ''
                ? iconRaw.trim()
                : undefined;

        const tabFields = panel.fields;
        const field_ids: string[] = [];
        if (isRecord(tabFields)) {
            for (const [yamlKey, definition] of Object.entries(tabFields)) {
                if (!isRecord(definition)) {
                    continue;
                }
                const resolvedId = String(definition.id ?? yamlKey).trim();
                if (resolvedId === '') {
                    continue;
                }
                merged[resolvedId] = definition;
                field_ids.push(resolvedId);
            }
        }

        tab_panels.push({
            id: tabId.trim(),
            label,
            ...(icon !== undefined ? { icon } : {}),
            field_ids,
        });
    }

    const { tabs: _omit, ...rest } = schema;
    return {
        ...rest,
        fields: merged,
        tab_panels,
    };
}

export function normalizeFields(
    schema?: Record<string, unknown> | null,
): FormFieldEntry[] {
    if (!isRecord(schema)) {
        return [];
    }
    const effective = isRecord(schema.tabs)
        ? mergeFormTabsIntoSchemaFields(schema)
        : schema;
    const rawFields = effective.fields;
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
