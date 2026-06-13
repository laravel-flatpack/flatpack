import type { FormFieldLabelShow } from '@/types/form-fields';

/** When `showLabel` is omitted, fields that historically stacked use `stacked`; toggles default to `inline`. */
export type FieldLabelDefaultAxis = 'stacked' | 'inline';

/**
 * Maps YAML `showLabel` to {@link Field} orientation and optional visually-hidden label (`none` → `sr-only`).
 */
export function resolveFormFieldLabelLayout(
    showLabel: FormFieldLabelShow | undefined,
    defaultAxis: FieldLabelDefaultAxis,
): {
    orientation: 'vertical' | 'horizontal';
    labelClassName?: string;
} {
    if (showLabel === 'none') {
        return {
            orientation: defaultAxis === 'inline' ? 'horizontal' : 'vertical',
            labelClassName: 'sr-only',
        };
    }
    if (showLabel === 'inline') {
        return { orientation: 'horizontal' };
    }
    if (showLabel === 'stacked') {
        return { orientation: 'vertical' };
    }
    return {
        orientation: defaultAxis === 'inline' ? 'horizontal' : 'vertical',
    };
}
