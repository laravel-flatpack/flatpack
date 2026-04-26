import type {
    FlatpackActionVariant,
    FlatpackSuccessRedirect,
    FlatpackTableRelationType,
} from '@/types/data-table';
import type { FlatpackActionCondition } from '@/types/flatpack-actions';
import type { FormFieldInputFormat, FormFieldPreset } from '@/types/form-fields';
import type {
    FlatpackListCompositionBulkActionYaml,
    FlatpackListCompositionColumnOptionsYaml,
    FlatpackListCompositionColumnsYaml,
} from '@/types/list-composition';

/**
 * Raw form composition from `form.yaml` (decoded JSON). Parity with `resources/schema/form.json`.
 * Runtime normalizes aliases (e.g. `date` → `date-picker`). Relation pickers use `type: combobox` with `relation`.
 */

export type FlatpackFormCompositionValidationRulesYaml = string | string[];

type FormCompositionButtonVariantYaml = FlatpackActionVariant | 'primary';

type FormCompositionHeaderActionSuccessRedirect =
    | FlatpackSuccessRedirect
    | true;

/** Form save / toolbar actions keyed by action name (YAML map). */
export type FlatpackFormCompositionHeaderActionYaml = {
    label: string;
} & ({ action: string; href?: never } | { href: string; action?: never }) & {
        icon?: string;
        variant?: FormCompositionButtonVariantYaml;
        success_message?: string;
        confirm?: boolean;
        success_redirect?: FormCompositionHeaderActionSuccessRedirect;
        enabled_if?: FlatpackActionCondition;
        visible_if?: FlatpackActionCondition;
        shortcut?: string;
    };

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
    variant?: FormCompositionButtonVariantYaml;
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
    showFixedToolbar?: boolean;
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
    showFixedToolbar?: boolean;
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
    | FlatpackFormCompositionFieldTableYaml;

export type FlatpackFormCompositionFieldsYaml = Record<
    string,
    FlatpackFormCompositionFieldYaml
>;

/**
 * Entity form composition from form.yaml (decoded JSON). Structural contract: `resources/schema/form.json`.
 */
export type FlatpackFormCompositionSchema = {
    [key: string]: unknown;
    name?: string;
    model?: string;
    icon?: string;
    fields?: FlatpackFormCompositionFieldsYaml;
    actions?: FlatpackFormCompositionActionsYaml;
};
