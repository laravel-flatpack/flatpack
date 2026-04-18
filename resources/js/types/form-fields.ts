import type {
    FlatpackDataTableBulkAction,
    FlatpackDataTableColumn,
    FlatpackDataTableSelectOptionStatus,
} from '@/types/data-table';

export type SelectFieldOption = {
    value: string;
    label: string;
    status?: FlatpackDataTableSelectOptionStatus;
};

export const formFieldPresetTypes = [
    'exact',
    'slug',
    'url',
    'camel',
    'file',
] as const;

export type FormFieldPresetType = (typeof formFieldPresetTypes)[number];

export type FormFieldPreset = {
    field: string;
    type: FormFieldPresetType;
};

export type FormFieldOutput = {
    show: boolean;
    label: string;
};

type FormFieldBase = {
    label: string;
    helperText?: string;
    /**
     * When set, the field auto-fills from {@link FormFieldPreset.field} until the user edits this field
     * or the field was non-empty when the form loaded (e.g. existing record).
     */
    preset?: FormFieldPreset;
    onValueChange?: (value: unknown) => void;
};

type WithPlaceholder = {
    placeholder?: string;
};

type TextFieldProps = FormFieldBase & WithPlaceholder;
type TextareaFieldProps = FormFieldBase &
    WithPlaceholder & {
        rows?: number;
    };
type SelectFieldProps = FormFieldBase &
    WithPlaceholder & {
        options: SelectFieldOption[];
        value?: unknown;
    };
type ComboboxFieldProps = FormFieldBase &
    WithPlaceholder & {
        options: SelectFieldOption[];
        multiple?: boolean;
    };
type DatePickerFieldProps = FormFieldBase & WithPlaceholder;
type DateRangePickerFieldProps = FormFieldBase & WithPlaceholder;
type TimePickerFieldProps = FormFieldBase &
    WithPlaceholder & {
        dateLabel?: string;
        datePlaceholder?: string;
        timeLabel?: string;
        timeDefaultValue?: string;
    };
type CheckboxFieldProps = FormFieldBase & {
    defaultChecked?: boolean;
};
type SwitchFieldProps = FormFieldBase & {
    defaultChecked?: boolean;
};
type RichTextFieldProps = FormFieldBase &
    WithPlaceholder & {
        showFixedToolbar?: boolean;
    };
type BlockEditorFieldProps = FormFieldBase &
    WithPlaceholder & {
        showFixedToolbar?: boolean;
    };
type TableFieldProps = FormFieldBase & {
    columns: FlatpackDataTableColumn[];
    data?: Record<string, unknown>[];
    bulkActions?: FlatpackDataTableBulkAction[];
    actions?: unknown[];
    reorderable?: boolean | string;
};

export type FormFieldProps =
    | ({ type: 'text' } & TextFieldProps)
    | ({ type: 'textarea' } & TextareaFieldProps)
    | ({ type: 'select' } & SelectFieldProps)
    | ({ type: 'combobox' } & ComboboxFieldProps)
    | ({ type: 'date-picker' } & DatePickerFieldProps)
    | ({ type: 'date-range-picker' } & DateRangePickerFieldProps)
    | ({ type: 'time-picker' } & TimePickerFieldProps)
    | ({ type: 'checkbox' } & CheckboxFieldProps)
    | ({ type: 'switch' } & SwitchFieldProps)
    | ({ type: 'rich-text' } & RichTextFieldProps)
    | ({ type: 'block-editor' } & BlockEditorFieldProps)
    | ({ type: 'table' } & TableFieldProps);

export type FormFieldType = FormFieldProps['type'];
