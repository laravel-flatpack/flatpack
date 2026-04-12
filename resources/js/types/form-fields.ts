import type {
    FlatpackDataTableColumn,
    FlatpackDataTableSelectOptionStatus,
} from '@/types/data-table';

export type SelectFieldOption = {
    value: string;
    label: string;
    status?: FlatpackDataTableSelectOptionStatus;
};

export type FormFieldOutput = {
    show: boolean;
    label: string;
};

type FormFieldBase = {
    label: string;
    helperText?: string;
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
        /** Current value (`null` / omitted = no selection, show placeholder). */
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
        /** Label for the time input (date uses `dateLabel` or top-level `label`). */
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
    /** Row objects keyed by column `id`s; may be supplied from catalog `value` in demos. */
    data?: Record<string, unknown>[];
    checkboxes?: boolean;
    actions?: unknown[];
    /**
     * When set, shows a drag handle per row and allows reordering (updates row order and reindexes the order column).
     * `true` uses `sort_order`; a string uses that column id.
     */
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
