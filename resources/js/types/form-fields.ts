import type {
    FlatpackDataTableBulkAction,
    FlatpackDataTableColumn,
    FlatpackDataTableDefaultSort,
    FlatpackDataTableSelectOptionStatus,
    FlatpackTableRelationType,
} from '@/types/data-table';
import type { FlatpackListCompositionColumnsYaml } from '@/types/list-composition';

export type SelectFieldOption = {
    value: string;
    label: string;
    status?: FlatpackDataTableSelectOptionStatus;
};

export type FormFieldPresetType =
    typeof import('@/lib/generated/composition-schema-keys').FORM_PRESET_TYPES[number];
export type FormFieldInputFormat = Exclude<FormFieldPresetType, 'exact'>;

export type FormFieldPreset = {
    field: string;
    type: FormFieldPresetType;
};

export type FormFieldTriggerAction =
    | 'show'
    | 'hide'
    | 'enable'
    | 'disable'
    | 'empty';

export type FormFieldTriggerCondition =
    | 'checked'
    | 'unchecked'
    | `value[${string}]`;

export type FormFieldTrigger = {
    action: FormFieldTriggerAction;
    field: string;
    condition: FormFieldTriggerCondition;
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
    trigger?: FormFieldTrigger;
    onValueChange?: (value: unknown) => void;
};

type WithPlaceholder = {
    placeholder?: string;
};

type TextFieldProps = FormFieldBase &
    WithPlaceholder & {
        format?: FormFieldInputFormat;
    };
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
        /** Remote relation picker / multi relation collection */
        relation?: string;
        relation_name?: string;
        relation_value?: string;
        remote?: boolean;
        /** Single-mode: emit `{ value, label }` instead of plain id string. */
        emitObject?: boolean;
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
    /** List page column shape (array or id-keyed map); normalized to DataTable columns in the mapper. */
    columns: FlatpackDataTableColumn[] | FlatpackListCompositionColumnsYaml;
    data?: Record<string, unknown>[];
    /** Same entries as {@link bulkActions}; list-style YAML key, normalized in the form field mapper. */
    bulk_actions?: unknown;
    bulkActions?: FlatpackDataTableBulkAction[];
    /** Toolbar buttons (Create / Add, …); YAML key {@code actions} (map or array). Normalized to DataTable props. */
    actions?: unknown;
    /** Same shape as {@link actions}; used when {@code actions} is omitted. If both are set, {@code actions} wins. */
    toolbar?: unknown;
    /** @deprecated Prefer {@link actions} or {@link toolbar}. Loaded when both are absent. */
    toolbar_actions?: unknown;
    /** @deprecated Prefer {@link actions} or {@link toolbar}. Lowest precedence. */
    toolbarActions?: unknown;
    reorderable?: boolean | string;
    pagination?: boolean;
    default_sort?: FlatpackDataTableDefaultSort;
    /** Relation-backed table: hydrate + sync as RelationRow[] */
    relation?: string;
    /** Model-backed table: row drawer saves persist immediately. */
    model?: string;
    relation_value?: string;
    limit?: number;
    /**
     * Eloquent relation class, usually from PHP when `model` is set on the form (see
     * `FormEmbeddedTableRelationTypeResolver`). May be set in YAML to override.
     */
    table_relation_type?: FlatpackTableRelationType;
    /**
     * When false, row clicks do not open the detail drawer; toolbar `create` still
     * opens the draft drawer for new rows.
     */
    row_detail_drawer?: boolean;
    /** CamelCase alias of {@link row_detail_drawer}. */
    openDetailDrawerOnRowClick?: boolean;
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

export type DataTableDrawerFormFieldType = FormFieldProps['type'];
