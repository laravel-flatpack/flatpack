import type { FormFieldProps, FormFieldType } from '@/types/form-fields';
import type { FieldRenderFn, RenderContext } from '@/types/demo-form-field-render';

function renderTextTextareaSelect(props: FormFieldProps, ctx: RenderContext) {
    const { type: _t, ...rest } = props as Extract<
        FormFieldProps,
        { type: 'text' | 'textarea' | 'select' }
    >;
    return {
        ...rest,
        id: ctx.entryId,
        placeholder: rest.placeholder ?? '',
        onValueChange: ctx.onValueChange,
    };
}

function renderCheckboxSwitch(props: FormFieldProps, ctx: RenderContext) {
    const {
        type: _t,
        helperText: _h,
        ...rest
    } = props as Extract<FormFieldProps, { type: 'checkbox' | 'switch' }>;
    return {
        ...rest,
        id: ctx.entryId,
        onValueChange: ctx.onValueChange,
    };
}

function renderRichBlock(props: FormFieldProps, ctx: RenderContext) {
    const { type: _t, ...rest } = props as Extract<
        FormFieldProps,
        { type: 'rich-text' | 'block-editor' }
    >;
    return {
        ...rest,
        id: ctx.entryId,
        onValueChange: ctx.onValueChange,
    };
}

function renderCombobox(props: FormFieldProps, ctx: RenderContext) {
    const p = props as Extract<FormFieldProps, { type: 'combobox' }>;
    return {
        id: ctx.entryId,
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

function renderDatePicker(props: FormFieldProps, ctx: RenderContext) {
    const p = props as Extract<FormFieldProps, { type: 'date-picker' }>;
    return {
        id: ctx.entryId,
        label: p.label,
        emptyLabel: p.placeholder ?? 'Pick a date',
        onValueChange: ctx.onValueChange,
    };
}

function renderDateRangePicker(props: FormFieldProps, ctx: RenderContext) {
    const p = props as Extract<FormFieldProps, { type: 'date-range-picker' }>;
    return {
        id: ctx.entryId,
        label: p.label,
        emptyLabel: p.placeholder ?? 'Pick a range',
        onValueChange: ctx.onValueChange,
    };
}

function renderTimePicker(props: FormFieldProps, ctx: RenderContext) {
    const p = props as Extract<FormFieldProps, { type: 'time-picker' }>;
    return {
        id: ctx.entryId,
        dateLabel: p.dateLabel ?? p.label,
        timeLabel: p.timeLabel ?? 'Time',
        dateEmptyLabel: p.datePlaceholder ?? p.placeholder ?? '',
        timeDefaultValue: p.timeDefaultValue ?? '09:00:00',
        onValueChange: ctx.onValueChange,
    };
}

const formFieldTypeToRenderProps: Record<FormFieldType, FieldRenderFn> = {
    text: renderTextTextareaSelect,
    textarea: renderTextTextareaSelect,
    select: renderTextTextareaSelect,
    checkbox: renderCheckboxSwitch,
    switch: renderCheckboxSwitch,
    'rich-text': renderRichBlock,
    'block-editor': renderRichBlock,
    combobox: renderCombobox,
    'date-picker': renderDatePicker,
    'date-range-picker': renderDateRangePicker,
    'time-picker': renderTimePicker,
};

export function formFieldPropsToRenderProps(
    props: FormFieldProps,
    ctx: RenderContext,
): Record<string, unknown> {
    return formFieldTypeToRenderProps[props.type](props, ctx);
}
