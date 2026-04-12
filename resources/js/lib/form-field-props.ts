import type {
    FormFieldPropsMapper,
    FormFieldRenderContext,
} from '@/types/form-field-render';
import type { FormFieldProps, FormFieldType } from '@/types/form-fields';

function mapTextTextareaSelect(
    props: FormFieldProps,
    ctx: FormFieldRenderContext,
) {
    const { type: _t, ...rest } = props as Extract<
        FormFieldProps,
        { type: 'text' | 'textarea' | 'select' }
    >;
    return {
        ...rest,
        id: ctx.fieldId,
        placeholder: rest.placeholder ?? '',
        onValueChange: ctx.onValueChange,
    };
}

function mapCheckboxSwitch(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const {
        type: _t,
        helperText: _h,
        ...rest
    } = props as Extract<FormFieldProps, { type: 'checkbox' | 'switch' }>;
    return {
        ...rest,
        id: ctx.fieldId,
        onValueChange: ctx.onValueChange,
    };
}

function mapRichBlock(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const { type: _t, ...rest } = props as Extract<
        FormFieldProps,
        { type: 'rich-text' | 'block-editor' }
    >;
    return {
        ...rest,
        id: ctx.fieldId,
        onValueChange: ctx.onValueChange,
    };
}

function mapCombobox(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const p = props as Extract<FormFieldProps, { type: 'combobox' }>;
    return {
        id: ctx.fieldId,
        label: p.label,
        multiple: p.multiple ?? false,
        items: p.options.map((o) => ({
            value: o.value,
            label: o.label,
        })),
        multiItems: p.options.map((o) => o.label),
        singlePlaceholder: p.placeholder ?? '',
        multiPlaceholder: p.placeholder ?? '',
        singleDescription: p.helperText,
        multiDescription: p.helperText,
        onValueChange: ctx.onValueChange,
    };
}

function mapDatePicker(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const p = props as Extract<FormFieldProps, { type: 'date-picker' }>;
    return {
        id: ctx.fieldId,
        label: p.label,
        emptyLabel: p.placeholder ?? 'Pick a date',
        onValueChange: ctx.onValueChange,
    };
}

function mapDateRangePicker(
    props: FormFieldProps,
    ctx: FormFieldRenderContext,
) {
    const p = props as Extract<FormFieldProps, { type: 'date-range-picker' }>;
    return {
        id: ctx.fieldId,
        label: p.label,
        emptyLabel: p.placeholder ?? 'Pick a range',
        onValueChange: ctx.onValueChange,
    };
}

function mapTimePicker(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const p = props as Extract<FormFieldProps, { type: 'time-picker' }>;
    return {
        id: ctx.fieldId,
        dateLabel: p.dateLabel ?? p.label,
        timeLabel: p.timeLabel ?? 'Time',
        dateEmptyLabel: p.datePlaceholder ?? p.placeholder ?? '',
        timeDefaultValue: p.timeDefaultValue ?? '09:00:00',
        onValueChange: ctx.onValueChange,
    };
}

const formFieldTypeToMapper: Record<FormFieldType, FormFieldPropsMapper> = {
    text: mapTextTextareaSelect,
    textarea: mapTextTextareaSelect,
    select: mapTextTextareaSelect,
    checkbox: mapCheckboxSwitch,
    switch: mapCheckboxSwitch,
    'rich-text': mapRichBlock,
    'block-editor': mapRichBlock,
    combobox: mapCombobox,
    'date-picker': mapDatePicker,
    'date-range-picker': mapDateRangePicker,
    'time-picker': mapTimePicker,
};

/**
 * Maps canonical {@link FormFieldProps} (e.g. from PHP schema / demo catalog) to the prop
 * bags expected by components under `components/form-fields/*`.
 */
export function mapFormFieldPropsToComponentProps(
    props: FormFieldProps,
    context: FormFieldRenderContext,
): Record<string, unknown> {
    return formFieldTypeToMapper[props.type](props, context);
}
