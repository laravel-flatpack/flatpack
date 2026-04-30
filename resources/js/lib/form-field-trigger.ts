import type { FormFieldProps, FormFieldTrigger } from '@/types/form-fields';

function parseValueCondition(condition: string): string | null {
    if (!condition.startsWith('value[') || !condition.endsWith(']')) {
        return null;
    }
    const value = condition.slice('value['.length, -1);
    return value.trim() === '' ? null : value;
}

export function triggerConditionMatches(
    sourceValue: unknown,
    condition: FormFieldTrigger['condition'],
): boolean {
    if (condition === 'checked') {
        return sourceValue === true;
    }
    if (condition === 'unchecked') {
        return sourceValue !== true;
    }

    const expected = parseValueCondition(condition);
    if (expected === null) {
        return false;
    }

    if (Array.isArray(sourceValue)) {
        return sourceValue.some((item) => String(item) === expected);
    }
    if (sourceValue === null || sourceValue === undefined) {
        return false;
    }
    return String(sourceValue) === expected;
}

export function evaluateFieldTrigger(
    trigger: FormFieldTrigger | undefined,
    values: Record<string, unknown>,
): {
    visible: boolean;
    disabled: boolean;
    shouldEmpty: boolean;
} {
    if (trigger === undefined) {
        return { visible: true, disabled: false, shouldEmpty: false };
    }

    const sourceField = trigger.field.trim();
    if (sourceField === '') {
        return { visible: true, disabled: false, shouldEmpty: false };
    }

    const matched = triggerConditionMatches(
        values[sourceField],
        trigger.condition,
    );

    switch (trigger.action) {
        case 'show':
            return { visible: matched, disabled: false, shouldEmpty: false };
        case 'hide':
            return { visible: !matched, disabled: false, shouldEmpty: false };
        case 'enable':
            return { visible: true, disabled: !matched, shouldEmpty: false };
        case 'disable':
            return { visible: true, disabled: matched, shouldEmpty: false };
        case 'empty':
            return { visible: true, disabled: false, shouldEmpty: matched };
        default:
            return { visible: true, disabled: false, shouldEmpty: false };
    }
}

export function emptyValueForField(field: FormFieldProps): unknown {
    switch (field.type) {
        case 'text':
        case 'textarea':
        case 'rich-text':
        case 'block-editor':
            return '';
        case 'checkbox':
        case 'switch':
            return false;
        case 'combobox':
            return field.multiple ? [] : null;
        case 'file-upload':
            return field.multiple ? [] : null;
        case 'table':
            return [];
        case 'select':
        case 'date-picker':
        case 'date-range-picker':
        case 'time-picker':
            return null;
        default:
            return null;
    }
}

export function valuesEqual(a: unknown, b: unknown): boolean {
    return JSON.stringify(a) === JSON.stringify(b);
}
