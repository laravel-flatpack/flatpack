/**
 * AUTO-GENERATED FILE — do not edit by hand.
 *
 * Regenerate with: `php artisan flatpack:generate-composition-schema-keys`
 * Source: resources/schema/form.json, resources/schema/list.json
 *
 * Intentionally small so the client bundle does not embed full schema JSON.
 */

/**
 * Top-level form.yaml keys from form.json `properties`.
 */
export const FORM_ROOT_PROPERTY_KEYS = [
    'actions',
    'fields',
    'icon',
    'model',
    'name',
] as const;

/** Identity map of list.json root property names; use `LIST_ROOT.nav_order`, `LIST_ROOT['bulk_actions']`, etc. */
export const LIST_ROOT = {
    actions: 'actions',
    bulk_actions: 'bulk_actions',
    columns: 'columns',
    filters: 'filters',
    icon: 'icon',
    model: 'model',
    name: 'name',
    nav_order: 'nav_order',
    reorderable: 'reorderable',
} as const;

export type ListRootKey = keyof typeof LIST_ROOT;

/**
 * Default DB column when list.yaml has `reorderable: true`. Not a list.yaml root key.
 */
export const DEFAULT_LIST_ROW_REORDER_COLUMN = 'sort_order' as const;

/**
 * Top-level list.yaml keys from list.json `properties`. Same names as keys of `LIST_ROOT`, sorted.
 */
export const LIST_ROOT_PROPERTY_KEYS = [
    'actions',
    'bulk_actions',
    'columns',
    'filters',
    'icon',
    'model',
    'name',
    'nav_order',
    'reorderable',
] as const;

/**
 * Canonical field types (yamlFormFieldType minus date/relation). Mirrors PHP `CompositionSchemaKeys::FORM_FIELD_TYPES_CANONICAL`.
 */
export const FORM_FIELD_TYPES_CANONICAL = [
    'block-editor',
    'checkbox',
    'combobox',
    'date-picker',
    'date-range-picker',
    'rich-text',
    'select',
    'switch',
    'table',
    'text',
    'textarea',
    'time-picker',
] as const;

/**
 * Union of form/list header action entry property keys.
 */
export const HEADER_ACTION_ENTRY_KEYS = [
    'action',
    'confirm',
    'disable_until_dirty',
    'href',
    'icon',
    'label',
    'shortcut',
    'success_message',
    'success_redirect',
    'variant',
] as const;

/**
 * bulk_actions entry keys (list.json bulkActionDefinition).
 */
export const LIST_BULK_ACTION_ENTRY_KEYS = [
    'action',
    'confirm',
    'icon',
    'label',
    'success_message',
    'success_redirect',
    'variant',
] as const;

/**
 * Union of list columnDefinition variant property keys.
 */
export const LIST_COLUMN_DEFINITION_PROPERTY_KEYS = [
    'actions',
    'detailDrawer',
    'editable',
    'format',
    'id',
    'invisible',
    'label',
    'options',
    'relation',
    'relationName',
    'relationValue',
    'relation_name',
    'relation_value',
    'searchable',
    'sortable',
    'timezone',
    'truncate',
    'type',
] as const;

/**
 * Field preset kinds (form.json preset.type enum). Mirrors PHP `CompositionSchemaKeys::FORM_PRESET_TYPES`.
 */
export const FORM_PRESET_TYPES = [
    'camel',
    'exact',
    'file',
    'slug',
    'url',
] as const;

/**
 * success_redirect enum. Mirrors PHP `CompositionSchemaKeys::SUCCESS_REDIRECT_VALUES`.
 */
export const SUCCESS_REDIRECT_VALUES = [
    'back',
    'create',
    'current',
    'edit',
    'list',
    'previous',
    'show',
    'stay',
] as const;

/**
 * Raw list column types (list.json listColumnType). Mirrors PHP `CompositionSchemaKeys::LIST_COLUMN_YAML_TYPES`.
 */
export const LIST_COLUMN_YAML_TYPES = [
    'actions',
    'badge',
    'date',
    'datetime',
    'relation',
    'select',
    'status',
    'text',
] as const;

/**
 * YAML button variants (includes primary). Mirrors PHP `CompositionSchemaKeys::BUTTON_VARIANT_VALUES`.
 */
export const BUTTON_VARIANT_VALUES = [
    'default',
    'destructive',
    'ghost',
    'link',
    'outline',
    'primary',
    'secondary',
] as const;

/**
 * Runtime/UI button variants (primary stripped). Mirrors PHP `CompositionSchemaKeys::BUTTON_VARIANT_UI_VALUES`.
 */
export const BUTTON_VARIANT_UI_VALUES = [
    'default',
    'destructive',
    'ghost',
    'link',
    'outline',
    'secondary',
] as const;

/**
 * Option status values. Mirrors PHP `CompositionSchemaKeys::OPTION_STATUS_VALUES`.
 */
export const OPTION_STATUS_VALUES = [
    'error',
    'info',
    'pending',
    'success',
    'warning',
] as const;

/**
 * List filter discriminator types (filterSelect / filterDate). Mirrors PHP `CompositionSchemaKeys::LIST_FILTER_TYPES`.
 */
export const LIST_FILTER_TYPES = ['date', 'select'] as const;

/**
 * Date filter mode enum. Mirrors PHP `CompositionSchemaKeys::LIST_FILTER_DATE_MODES`.
 */
export const LIST_FILTER_DATE_MODES = ['exact', 'from'] as const;
