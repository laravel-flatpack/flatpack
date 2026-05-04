import type {
    FlatpackDataTableBulkAction,
    FlatpackDataTableColumn,
    FlatpackDataTableDefaultSort,
    FlatpackDataTableSelectOptionStatus,
    FlatpackTableRelationType,
} from '@/types/data-table';
import type { FlatpackListCompositionColumnsYaml } from '@/types/list-composition';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

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

/** Canonical tokens after PHP normalization; fraction aliases may appear before normalize. */
export type FieldSpanNamed =
    | 'full'
    | 'half'
    | 'two_thirds'
    | 'third'
    | 'quarter';

export type FieldSpanFraction = '1/2' | '2/3' | '1/3' | '1/4';

export type FieldSpan = FieldSpanNamed | FieldSpanFraction;

/** Layout for grouped fields (`fieldset` object form). Omitted in JSON when `card` (default). */
export type FieldsetVariant = 'card' | 'minimal' | 'plain' | 'none';

/** Label placement for a field (`form.yaml` `showLabel`). */
export type FormFieldLabelShow = 'stacked' | 'inline' | 'none';

/** Normalized server-side to `{ label, icon?, variant?, collapsed? }`; authors may use a string or object in YAML. */
export type FieldsetDefinition = {
    label: string;
    icon?: string;
    variant?: FieldsetVariant;
    /**
     * When set, section is collapsible (Radix Collapsible). True = initially collapsed; false = initially expanded.
     * Omitted for non-collapsible. Stripped server-side when `variant` is `none`.
     */
    collapsed?: boolean;
};

type FormFieldBase = {
    label: string;
    /** When true, control is non-interactive; combined with {@link FormFieldTrigger} in the form shell. */
    disabled?: boolean;
    /** Label vs control layout; omit for each field type’s historical default. */
    showLabel?: FormFieldLabelShow;
    helperText?: string;
    /** Responsive grid span (YAML `span`); normalized server-side to {@link FieldSpanNamed}. */
    span?: FieldSpan;
    /**
     * Optional section group: adjacent fields with the same normalized label and variant share one wrapper
     * (`fieldset` string or `{ label, icon?, variant? }`).
     */
    fieldset?: string | FieldsetDefinition;
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
        toolbar?: boolean;
    };
type BlockEditorFieldProps = FormFieldBase &
    WithPlaceholder & {
        toolbar?: boolean;
    };
type WidgetFormFieldProps = FormFieldBase & {
    /** Widget definitions keyed by id; runtime payloads merged from the form page `widgets` prop. */
    widget: Record<string, Record<string, unknown>>;
};
export type FileUploadStoredFile = {
    disk?: string;
    path?: string;
    url?: string;
    name?: string;
    mime_type?: string;
    size?: number;
    visibility?: string;
    collection?: string;
};
type FileUploadFieldProps = FormFieldBase & {
    mode: 'relation' | 'url' | 'image' | 'file';
    multiple?: boolean;
    max_files?: number;
    max_size_kb?: number;
    accept?: string | string[];
    directory?: string;
    disk?: string;
    visibility?: 'public' | 'private';
    relation?: string;
    /** Relation mode: public method name on the entity model (receives upload metadata rows). */
    callback?: string;
    collection?: string;
    target_column?: string;
    persist_as?: 'string' | 'json';
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
    showColumnsVisibility?: boolean;
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

export type RepeaterDisplayMode = 'accordion' | 'builder';

/** Normalized form toolbar row (`type: toolbar`). */
export type ToolbarFieldAlign =
    | 'left'
    | 'right'
    | 'center'
    | 'start'
    | 'end'
    | 'spaced';

export type ToolbarFieldProps = {
    /** Optional; omit for a chromeless button row. */
    label?: string;
    disabled?: boolean;
    showLabel?: FormFieldLabelShow;
    helperText?: string;
    span?: FieldSpan;
    fieldset?: string | FieldsetDefinition;
    trigger?: FormFieldTrigger;
    /** Normalized header-action rows from PHP ({@link HeaderActions}-compatible). */
    actions: FlatpackListHeaderAction[];
    align?: ToolbarFieldAlign;
};

export type RepeaterFieldProps = FormFieldBase & {
    /** Hydrated row list from the parent record; omitted in YAML, present at runtime. */
    value?: unknown;
    /** Inline per-item field definitions. Mutually exclusive with `form`. */
    fields?: Record<string, Record<string, unknown>>;
    /** Relative YAML fragment path (resolved server-side); mutually exclusive with `fields`. */
    form?: string;
    groups?: unknown;
    prompt?: string;
    displayMode?: RepeaterDisplayMode;
    itemsExpanded?: boolean;
    /** Field key for collapsed title, or `false` with min/max 1 to hide the row title bar. */
    titleFrom?: string | false;
    minItems?: number;
    maxItems?: number;
    groupKeyFrom?: string;
    showReorder?: boolean;
    showDuplicate?: boolean;
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
    | ({ type: 'widget' } & WidgetFormFieldProps)
    | ({ type: 'toolbar' } & ToolbarFieldProps)
    | ({ type: 'file-upload' } & FileUploadFieldProps)
    | ({ type: 'table' } & TableFieldProps)
    | ({ type: 'repeater' } & RepeaterFieldProps);

export type FormFieldType = FormFieldProps['type'];
