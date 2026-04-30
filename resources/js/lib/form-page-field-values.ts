import type { DateRange } from 'react-day-picker';
import { localDateSegment } from '@/lib/data-table-utils';
import { relationComboboxRemoteProps } from '@/lib/relation-combobox-remote';
import type { FormFieldProps } from '@/types/form-fields';

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
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

    const datePrefix = /^(\d{4})-(\d{2})-(\d{2})(?:[ T].*)?$/.exec(trimmed);
    if (datePrefix) {
        const [, year, month, day] = datePrefix;
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

export function serializeFieldValue(
    field: FormFieldProps,
    value: unknown,
): unknown {
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

export function componentValueProps(
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
        case 'file-upload':
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

export function relationRemoteProps(
    field: FormFieldProps,
    fieldId: string,
    entity: string,
): Record<string, unknown> {
    if (field.type !== 'combobox') {
        return {};
    }

    const relationField = field as FormFieldProps & {
        relation?: unknown;
    };
    if (
        typeof relationField.relation !== 'string' ||
        relationField.relation.trim() === ''
    ) {
        return {};
    }

    return relationComboboxRemoteProps({
        kind: 'field',
        entity,
        fieldId,
    });
}
