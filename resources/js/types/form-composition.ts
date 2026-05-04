import type { FlatpackFormTabPanelLayout } from '@/lib/form-schema';
import type {
    FlatpackActionTarget,
    FlatpackActionUiMeta,
    FlatpackActionVisibilityMeta,
    FlatpackYamlButtonVariant,
} from '@/types/action-shared';
import type {
    FlatpackSuccessRedirect,
    FlatpackTableRelationType,
} from '@/types/data-table';
import type {
    FormFieldInputFormat,
    FormFieldPreset,
    FormFieldTrigger,
    ToolbarFieldAlign,
} from '@/types/form-fields';
import type {
    FlatpackListCompositionBulkActionYaml,
    FlatpackListCompositionColumnOptionsYaml,
    FlatpackListCompositionColumnsYaml,
} from '@/types/list-composition';

/** Form schema shape consumed by the React form page (already normalized by PHP). */

export type FlatpackFormCompositionValidationRulesYaml = string | string[];

type FormCompositionHeaderActionSuccessRedirect =
    | FlatpackSuccessRedirect
    | true;

/** Form save / toolbar actions keyed by action name (YAML map). */
export type FlatpackFormCompositionHeaderActionYaml = {
    label: string;
} & FlatpackActionTarget &
    FlatpackActionUiMeta<FormCompositionHeaderActionSuccessRedirect> &
    FlatpackActionVisibilityMeta;

export type FlatpackFormCompositionActionsYaml = Record<
    string,
    FlatpackFormCompositionHeaderActionYaml
>;

type FormCompositionFieldTableDataRowYaml = Record<string, unknown>;

/** Toolbar buttons above an embedded {@code type: table} field (distinct from column-level actions). */
export type FlatpackFormCompositionFieldTableActionYaml = {
    label: string;
    action: string;
    icon?: string;
    variant?: FlatpackYamlButtonVariant;
};

export type FlatpackFormCompositionFieldTextYaml = {
    type: 'text';
    id?: string;
    label: string;
    helperText?: string;
    placeholder?: string;
    required?: boolean;
    rules?: FlatpackFormCompositionValidationRulesYaml;
    value?: unknown;
    preset?: FormFieldPreset;
    format?: FormFieldInputFormat;
    trigger?: FormFieldTrigger;
};

export type FlatpackFormCompositionFieldTextareaYaml = {
    type: 'textarea';
    id?: string;
    label: string;
    helperText?: string;
    placeholder?: string;
    required?: boolean;
    rules?: FlatpackFormCompositionValidationRulesYaml;
    value?: unknown;
    rows?: number;
    preset?: FormFieldPreset;
    trigger?: FormFieldTrigger;
};

export type FlatpackFormCompositionFieldSelectYaml = {
    type: 'select';
    id?: string;
    label: string;
    helperText?: string;
    placeholder?: string;
    required?: boolean;
    rules?: FlatpackFormCompositionValidationRulesYaml;
    value?: unknown;
    options: FlatpackListCompositionColumnOptionsYaml;
    trigger?: FormFieldTrigger;
};

export type FlatpackFormCompositionFieldComboboxYaml = {
    type: 'combobox';
    id?: string;
    label: string;
    helperText?: string;
    placeholder?: string;
    required?: boolean;
    rules?: FlatpackFormCompositionValidationRulesYaml;
    value?: unknown;
    options?: FlatpackListCompositionColumnOptionsYaml;
    multiple?: boolean;
    relation?: string;
    relation_name?: string;
    relation_value?: string;
    remote?: boolean;
    trigger?: FormFieldTrigger;
};

export type FlatpackFormCompositionFieldDateYaml = {
    type: 'date';
    id?: string;
    label: string;
    helperText?: string;
    placeholder?: string;
    required?: boolean;
    rules?: FlatpackFormCompositionValidationRulesYaml;
    value?: unknown;
    trigger?: FormFieldTrigger;
};

export type FlatpackFormCompositionFieldDatePickerYaml = {
    type: 'date-picker';
    id?: string;
    label: string;
    helperText?: string;
    placeholder?: string;
    required?: boolean;
    rules?: FlatpackFormCompositionValidationRulesYaml;
    value?: unknown;
    trigger?: FormFieldTrigger;
};

export type FlatpackFormCompositionFieldDateRangePickerYaml = {
    type: 'date-range-picker';
    id?: string;
    label: string;
    helperText?: string;
    placeholder?: string;
    required?: boolean;
    rules?: FlatpackFormCompositionValidationRulesYaml;
    value?: unknown;
    trigger?: FormFieldTrigger;
};

export type FlatpackFormCompositionFieldTimePickerYaml = {
    type: 'time-picker';
    id?: string;
    label: string;
    helperText?: string;
    placeholder?: string;
    required?: boolean;
    rules?: FlatpackFormCompositionValidationRulesYaml;
    value?: unknown;
    dateLabel?: string;
    datePlaceholder?: string;
    timeLabel?: string;
    timeDefaultValue?: string;
    trigger?: FormFieldTrigger;
};

export type FlatpackFormCompositionFieldCheckboxYaml = {
    type: 'checkbox';
    id?: string;
    label: string;
    helperText?: string;
    required?: boolean;
    rules?: FlatpackFormCompositionValidationRulesYaml;
    value?: unknown;
    defaultChecked?: boolean;
    trigger?: FormFieldTrigger;
};

export type FlatpackFormCompositionFieldSwitchYaml = {
    type: 'switch';
    id?: string;
    label: string;
    helperText?: string;
    required?: boolean;
    rules?: FlatpackFormCompositionValidationRulesYaml;
    value?: unknown;
    defaultChecked?: boolean;
    trigger?: FormFieldTrigger;
};

export type FlatpackFormCompositionFieldRichTextYaml = {
    type: 'rich-text';
    id?: string;
    label: string;
    helperText?: string;
    placeholder?: string;
    required?: boolean;
    rules?: FlatpackFormCompositionValidationRulesYaml;
    value?: unknown;
    toolbar?: boolean;
    trigger?: FormFieldTrigger;
};

export type FlatpackFormCompositionFieldBlockEditorYaml = {
    type: 'block-editor';
    id?: string;
    label: string;
    helperText?: string;
    placeholder?: string;
    required?: boolean;
    rules?: FlatpackFormCompositionValidationRulesYaml;
    value?: unknown;
    toolbar?: boolean;
    trigger?: FormFieldTrigger;
};

export type FlatpackFormCompositionFieldFileUploadYaml = {
    type: 'file-upload';
    id?: string;
    label: string;
    helperText?: string;
    required?: boolean;
    rules?: FlatpackFormCompositionValidationRulesYaml;
    value?: unknown;
    mode: 'relation' | 'url' | 'image' | 'file';
    multiple?: boolean;
    max_files?: number;
    max_size_kb?: number;
    accept?: string | string[];
    directory?: string;
    disk?: string;
    visibility?: 'public' | 'private';
    relation?: string;
    callback?: string;
    collection?: string;
    target_column?: string;
    persist_as?: 'string' | 'json';
    trigger?: FormFieldTrigger;
};

export type FlatpackFormCompositionFieldTableYaml = {
    type: 'table';
    id?: string;
    label: string;
    helperText?: string;
    required?: boolean;
    rules?: FlatpackFormCompositionValidationRulesYaml;
    value?: unknown;
    columns: FlatpackListCompositionColumnsYaml;
    data?: FormCompositionFieldTableDataRowYaml[];
    bulkActions?: FlatpackListCompositionBulkActionYaml[];
    actions?:
        | Record<string, FlatpackFormCompositionFieldTableActionYaml>
        | FlatpackFormCompositionFieldTableActionYaml[];
    /** Same shape as {@link actions}; used when {@code actions} is omitted. */
    toolbar?:
        | Record<string, FlatpackFormCompositionFieldTableActionYaml>
        | FlatpackFormCompositionFieldTableActionYaml[];
    /** @deprecated Prefer {@link actions} or {@link toolbar}. */
    toolbar_actions?:
        | Record<string, FlatpackFormCompositionFieldTableActionYaml>
        | FlatpackFormCompositionFieldTableActionYaml[];
    /** @deprecated Prefer {@link actions} or {@link toolbar}. */
    toolbarActions?:
        | Record<string, FlatpackFormCompositionFieldTableActionYaml>
        | FlatpackFormCompositionFieldTableActionYaml[];
    reorderable?: boolean | string;
    showColumnsVisibility?: boolean;
    model?: string;
    relation?: string;
    relation_value?: string;
    limit?: number;
    /**
     * Eloquent relation class; usually from PHP. May be set in YAML to override
     * `FormEmbeddedTableRelationTypeResolver`.
     */
    table_relation_type?: FlatpackTableRelationType;
    /** When false, row clicks do not open the detail drawer (`create` toolbar draft flow unchanged). */
    row_detail_drawer?: boolean;
    trigger?: FormFieldTrigger;
};

/** Inline toolbar (`type: toolbar`): same {@code actions} map shape as top-level form {@link FlatpackFormCompositionActionsYaml}. */
export type FlatpackFormCompositionFieldToolbarYaml = {
    type: 'toolbar';
    id?: string;
    label?: string;
    helperText?: string;
    actions: FlatpackFormCompositionActionsYaml;
    align?: ToolbarFieldAlign;
    span?: string;
    fieldset?: string | { label: string; icon?: string; variant?: string };
    showLabel?: string;
    trigger?: FormFieldTrigger;
};

export type FlatpackFormCompositionFieldYaml =
    | FlatpackFormCompositionFieldTextYaml
    | FlatpackFormCompositionFieldTextareaYaml
    | FlatpackFormCompositionFieldSelectYaml
    | FlatpackFormCompositionFieldComboboxYaml
    | FlatpackFormCompositionFieldDateYaml
    | FlatpackFormCompositionFieldDatePickerYaml
    | FlatpackFormCompositionFieldDateRangePickerYaml
    | FlatpackFormCompositionFieldTimePickerYaml
    | FlatpackFormCompositionFieldCheckboxYaml
    | FlatpackFormCompositionFieldSwitchYaml
    | FlatpackFormCompositionFieldRichTextYaml
    | FlatpackFormCompositionFieldBlockEditorYaml
    | FlatpackFormCompositionFieldFileUploadYaml
    | FlatpackFormCompositionFieldTableYaml
    | FlatpackFormCompositionFieldToolbarYaml;

export type FlatpackFormCompositionFieldsYaml = Record<
    string,
    FlatpackFormCompositionFieldYaml
>;

export type FlatpackFormSchema = {
    name?: string;
    model?: string;
    icon?: string;
    fields?: FlatpackFormCompositionFieldsYaml;
    tab_panels?: FlatpackFormTabPanelLayout[];
    actions?: FlatpackFormCompositionActionsYaml;
    /** Field ids merged from `sidebar` into `fields`; rendered in the secondary column when present. */
    sidebar_field_ids?: string[];
};
