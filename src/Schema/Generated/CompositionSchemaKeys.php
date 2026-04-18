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
     * Top-level keys from list.json `properties` (entity list.yaml).
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
        'order',
        'reorderable',
        'sort_order',
    ];

    /**
     * list.json root property `actions`.
     */
    public const string LIST_ROOT_ACTIONS = 'actions';

    /**
     * list.json root property `bulk_actions`.
     */
    public const string LIST_ROOT_BULK_ACTIONS = 'bulk_actions';

    /**
     * list.json root property `columns`.
     */
    public const string LIST_ROOT_COLUMNS = 'columns';

    /**
     * list.json root property `filters`.
     */
    public const string LIST_ROOT_FILTERS = 'filters';

    /**
     * list.json root property `icon`.
     */
    public const string LIST_ROOT_ICON = 'icon';

    /**
     * list.json root property `model`.
     */
    public const string LIST_ROOT_MODEL = 'model';

    /**
     * list.json root property `name`.
     */
    public const string LIST_ROOT_NAME = 'name';

    /**
     * list.json root property `order`.
     */
    public const string LIST_ROOT_ORDER = 'order';

    /**
     * list.json root property `reorderable`.
     */
    public const string LIST_ROOT_REORDERABLE = 'reorderable';

    /**
     * list.json root property `sort_order`.
     */
    public const string LIST_ROOT_SORT_ORDER = 'sort_order';

    /**
     * Canonical field types after YAML aliases are stripped (see yamlFormFieldType enum minus date/relation).
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
