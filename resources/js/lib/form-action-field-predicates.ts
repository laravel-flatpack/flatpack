import { valuesEqual } from '@/lib/form-field-trigger';

/** When {@link ActionInactiveContext.formValues} is omitted, field predicates never pass. */
export function evaluateFormFieldPredicate(
    predicateKey:
        | 'form.field_eq'
        | 'form.field_in'
        | 'form.field_truthy'
        | 'form.field_present'
        | 'form.field_null',
    payload: unknown,
    formValues: Record<string, unknown> | undefined,
): boolean {
    if (formValues === undefined) {
        return false;
    }

    switch (predicateKey) {
        case 'form.field_eq':
            return evaluateFieldEq(payload, formValues);
        case 'form.field_in':
            return evaluateFieldIn(payload, formValues);
        case 'form.field_truthy':
            return evaluateFieldTruthy(payload, formValues);
        case 'form.field_present':
            return evaluateFieldPresent(payload, formValues);
        case 'form.field_null':
            return evaluateFieldNull(payload, formValues);
        default:
            return false;
    }
}

function fieldFromPayload(payload: unknown): string | null {
    if (
        typeof payload !== 'object' ||
        payload === null ||
        !('field' in payload)
    ) {
        return null;
    }
    const field = String((payload as { field: unknown }).field).trim();
    return field === '' ? null : field;
}

function evaluateFieldEq(
    payload: unknown,
    formValues: Record<string, unknown>,
): boolean {
    if (
        typeof payload !== 'object' ||
        payload === null ||
        !('field' in payload) ||
        !('value' in payload)
    ) {
        return false;
    }
    const field = fieldFromPayload(payload);
    if (field === null) {
        return false;
    }
    const expected = (payload as { value: unknown }).value;
    const actual = objectFieldValue(formValues, field);
    return valuesEqual(actual, expected);
}

function evaluateFieldIn(
    payload: unknown,
    formValues: Record<string, unknown>,
): boolean {
    if (
        typeof payload !== 'object' ||
        payload === null ||
        !('field' in payload) ||
        !('values' in payload)
    ) {
        return false;
    }
    const field = fieldFromPayload(payload);
    if (field === null) {
        return false;
    }
    const allowed = (payload as { values: unknown }).values;
    if (!Array.isArray(allowed) || allowed.length === 0) {
        return false;
    }
    const actual = objectFieldValue(formValues, field);

    if (Array.isArray(actual)) {
        return actual.some((item) =>
            allowed.some((candidate) => valuesEqual(item, candidate)),
        );
    }
    return allowed.some((candidate) => valuesEqual(actual, candidate));
}

function evaluateFieldTruthy(
    payload: unknown,
    formValues: Record<string, unknown>,
): boolean {
    const field = fieldFromPayload(payload);
    if (field === null) {
        return false;
    }
    return isFormFieldTruthy(objectFieldValue(formValues, field));
}

function evaluateFieldPresent(
    payload: unknown,
    formValues: Record<string, unknown>,
): boolean {
    const field = fieldFromPayload(payload);
    if (field === null) {
        return false;
    }
    const v = objectFieldValue(formValues, field);
    return v !== null && v !== undefined;
}

function evaluateFieldNull(
    payload: unknown,
    formValues: Record<string, unknown>,
): boolean {
    const field = fieldFromPayload(payload);
    if (field === null) {
        return false;
    }
    if (!Object.hasOwn(formValues, field)) {
        return true;
    }
    const v = formValues[field];
    return v === null || v === undefined;
}

function objectFieldValue(
    formValues: Record<string, unknown>,
    field: string,
): unknown {
    if (!Object.hasOwn(formValues, field)) {
        return undefined;
    }
    return formValues[field];
}

function isFormFieldTruthy(value: unknown): boolean {
    if (value === null || value === undefined || value === false) {
        return false;
    }
    if (value === 0 || value === '') {
        return false;
    }
    if (Array.isArray(value)) {
        return value.length > 0;
    }
    if (typeof value === 'string') {
        return value.trim() !== '';
    }
    if (typeof value === 'number') {
        return !Number.isNaN(value) && value !== 0;
    }
    if (typeof value === 'object') {
        return Object.keys(value as object).length > 0;
    }
    return Boolean(value);
}
