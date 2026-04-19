<?php

declare(strict_types=1);

namespace Flatpack\Schema\Generated;

/**
 * AUTO-GENERATED FILE — do not edit by hand.
 *
 * Regenerate with: `php artisan flatpack:generate-composition-schema-keys`
 * Source: resources/schema/form.json, resources/schema/list.json
 */
final class CompositionSchemaKeys
{
    /**
     * Top-level keys from form.json `properties` (entity form.yaml).
     *
     * @var list<string>
     */
    public const array FORM_ROOT_PROPERTY_KEYS = [
        'actions',
        'fields',
        'icon',
        'model',
        'name',
    ];

    /**
     * Identity map of list.json root property names.
     * Use `LIST_ROOT['columns']` (or array access with a variable key) for string tokens.
     *
     * @var array<string, string>
     */
    public const array LIST_ROOT = [
        'actions' => 'actions',
        'bulk_actions' => 'bulk_actions',
        'columns' => 'columns',
        'filters' => 'filters',
        'icon' => 'icon',
        'model' => 'model',
        'name' => 'name',
        'nav_order' => 'nav_order',
        'reorderable' => 'reorderable',
        'row_click_edit' => 'row_click_edit',
    ];

    /**
     * Default database column for row reorder when list.yaml has `reorderable: true`.
     * Not a list.yaml root property; unrelated to sidebar `nav_order`.
     */
    public const string DEFAULT_LIST_ROW_REORDER_COLUMN = 'sort_order';

    /**
     * Top-level keys from list.json `properties` (entity list.yaml). Same names as keys of `LIST_ROOT`, sorted.
     *
     * @var list<string>
     */
    public const array LIST_ROOT_PROPERTY_KEYS = [
        'actions',
        'bulk_actions',
        'columns',
        'filters',
        'icon',
        'model',
        'name',
        'nav_order',
        'reorderable',
        'row_click_edit',
    ];

    /**
     * Canonical field types after YAML aliases are stripped (see yamlFormFieldType enum minus date).
     *
     * @var list<string>
     */
    public const array FORM_FIELD_TYPES_CANONICAL = [
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
    ];

    /**
     * Union of nested keys allowed on toolbar/header action entries (form headerActionDefinition ∪ list headerActionEntry).
     *
     * @var list<string>
     */
    public const array HEADER_ACTION_ENTRY_KEYS = [
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
    ];

    /**
     * Nested keys for each bulk_actions entry (list.json bulkActionDefinition).
     *
     * @var list<string>
     */
    public const array LIST_BULK_ACTION_ENTRY_KEYS = [
        'action',
        'confirm',
        'icon',
        'label',
        'success_message',
        'success_redirect',
        'variant',
    ];

    /**
     * Nested keys for each list column `actions` button (list.json columnActionButton).
     *
     * @var list<string>
     */
    public const array LIST_COLUMN_ACTION_BUTTON_ENTRY_KEYS = [
        'action',
        'confirm',
        'href',
        'icon',
        'label',
        'success_message',
        'success_redirect',
        'variant',
    ];

    /**
     * Union of property keys across list columnDefinition oneOf variants.
     *
     * @var list<string>
     */
    public const array LIST_COLUMN_DEFINITION_PROPERTY_KEYS = [
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
        'required',
        'rules',
        'searchable',
        'sortable',
        'timezone',
        'truncate',
        'type',
    ];

    /**
     * form.json `$defs.preset.properties.type` enum (field preset kinds).
     *
     * @var list<string>
     */
    public const array FORM_PRESET_TYPES = [
        'camel',
        'exact',
        'file',
        'slug',
        'url',
    ];

    /**
     * form.json / list.json `$defs.successRedirect` enum.
     *
     * @var list<string>
     */
    public const array SUCCESS_REDIRECT_VALUES = [
        'back',
        'create',
        'current',
        'edit',
        'list',
        'previous',
        'show',
        'stay',
    ];

    /**
     * list.json `$defs.listColumnType` enum (raw YAML column types).
     *
     * @var list<string>
     */
    public const array LIST_COLUMN_YAML_TYPES = [
        'actions',
        'badge',
        'date',
        'datetime',
        'relation',
        'select',
        'status',
        'text',
    ];

    /**
     * list.json `$defs.columnGeneric.properties.type` enum (text-like column kinds when type is set).
     *
     * @var list<string>
     */
    public const array LIST_COLUMN_GENERIC_YAML_TYPES = [
        'badge',
        'status',
        'text',
    ];

    /**
     * form.json `$defs.buttonVariant` enum (includes YAML alias `primary`).
     *
     * @var list<string>
     */
    public const array BUTTON_VARIANT_VALUES = [
        'default',
        'destructive',
        'ghost',
        'link',
        'outline',
        'primary',
        'secondary',
    ];

    /**
     * Button variants after normalizing `primary` → `default` (shadcn / runtime output).
     *
     * @var list<string>
     */
    public const array BUTTON_VARIANT_UI_VALUES = [
        'default',
        'destructive',
        'ghost',
        'link',
        'outline',
        'secondary',
    ];

    /**
     * form.json `$defs.optionStatus` enum (select/column option status).
     *
     * @var list<string>
     */
    public const array OPTION_STATUS_VALUES = [
        'error',
        'info',
        'pending',
        'success',
        'warning',
    ];

    /**
     * list.json filterSelect / filterDate `type` const values.
     *
     * @var list<string>
     */
    public const array LIST_FILTER_TYPES = [
        'date',
        'select',
    ];

    /**
     * list.json `$defs.filterDate.properties.mode` enum.
     *
     * @var list<string>
     */
    public const array LIST_FILTER_DATE_MODES = [
        'exact',
        'from',
    ];
}
