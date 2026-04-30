<?php

declare(strict_types=1);

use Flatpack\Tests\Support\CompositionSchemaAsserter;

describe('form composition schema (resources/schema/form.json)', function () {
    it('accepts a minimal valid form with a text field', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'name' => 'Post',
            'model' => 'Flatpack\\Tests\\Models\\Post',
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts tabs-only form with nested fields', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'name' => 'Post',
            'model' => 'Flatpack\\Tests\\Models\\Post',
            'tabs' => [
                'profile' => [
                    'label' => 'Profile',
                    'icon' => 'user',
                    'fields' => [
                        'title' => [
                            'type' => 'text',
                            'label' => 'Title',
                        ],
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts text fields with preset', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'label' => 'Title',
                ],
                'slug' => [
                    'type' => 'text',
                    'label' => 'Slug',
                    'preset' => [
                        'field' => 'title',
                        'type' => 'slug',
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('rejects a select field that includes preset (wrong type branch)', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'fields' => [
                'role' => [
                    'type' => 'select',
                    'label' => 'Role',
                    'options' => [
                        ['value' => 'a', 'label' => 'A'],
                    ],
                    'preset' => [
                        'field' => 'title',
                        'type' => 'exact',
                    ],
                ],
            ],
        ]);

        expect($errors)->not->toBeEmpty();
    });

    it('requires options on select fields', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'fields' => [
                'role' => [
                    'type' => 'select',
                    'label' => 'Role',
                ],
            ],
        ]);

        expect($errors)->not->toBeEmpty();
    });

    it('accepts combobox with relation for remote relation picker', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'fields' => [
                'category_id' => [
                    'type' => 'combobox',
                    'label' => 'Category',
                    'relation' => 'category',
                    'relation_name' => 'name',
                    'relation_value' => 'id',
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts enabled_if on form header actions', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'actions' => [
                'save' => [
                    'label' => 'Save',
                    'action' => 'save',
                    'enabled_if' => [
                        'all' => [
                            ['form.dirty' => true],
                        ],
                        'message' => 'Make changes first.',
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts visible_if on form header actions', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'actions' => [
                'publish' => [
                    'label' => 'Publish',
                    'action' => 'save',
                    'visible_if' => [
                        'all' => [
                            ['form.mode_in' => ['edit']],
                        ],
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('rejects deprecated disable_until_dirty on form actions', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'actions' => [
                'save' => [
                    'label' => 'Save',
                    'action' => 'save',
                    'disable_until_dirty' => true,
                ],
            ],
        ]);

        expect($errors)->not->toBeEmpty();
    });

    it('rejects obsolete form field type relation', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'fields' => [
                'category_id' => [
                    'type' => 'relation',
                    'label' => 'Category',
                    'relation' => 'category',
                ],
            ],
        ]);

        expect($errors)->not->toBeEmpty();
    });
});

describe('list composition schema (resources/schema/list.json)', function () {
    it('accepts tabs-only list with nested columns', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'name' => 'Posts',
            'model' => 'Flatpack\\Tests\\Models\\Post',
            'tabs' => [
                'main' => [
                    'label' => 'Main',
                    'columns' => [
                        [
                            'id' => 'title',
                            'type' => 'text',
                            'label' => 'Title',
                        ],
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts tabs with scope and without tab-level columns', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'name' => 'Posts',
            'model' => 'Flatpack\\Tests\\Models\\Post',
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
            'tabs' => [
                'all' => [
                    'label' => 'All',
                ],
                'trashed' => [
                    'label' => 'Trash',
                    'scope' => 'trashed',
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts tabs with filter and bulk action overrides', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'name' => 'Posts',
            'model' => 'Flatpack\\Tests\\Models\\Post',
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
            'tabs' => [
                'drafts' => [
                    'label' => 'Drafts',
                    'scope' => 'draftOnly',
                    'reorderable' => false,
                    'row_click' => 'none',
                    'filters' => [
                        'status' => [
                            'type' => 'select',
                            'options' => [
                                ['value' => 'draft', 'label' => 'Draft'],
                            ],
                        ],
                    ],
                    'bulkActions' => [
                        'publish' => [
                            'label' => 'Publish',
                            'action' => 'publish',
                        ],
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts columns as a list with select and options', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'name' => 'Posts',
            'model' => 'Flatpack\\Tests\\Models\\Post',
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
                [
                    'id' => 'status',
                    'type' => 'select',
                    'label' => 'Status',
                    'options' => [
                        ['value' => 'draft', 'label' => 'Draft'],
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts relation columns with snake_case relation keys', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'author',
                    'type' => 'relation',
                    'label' => 'Author',
                    'relation' => 'author',
                    'relation_name' => 'name',
                    'relation_value' => 'id',
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts relation columns with camelCase relation keys as read alias', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'author',
                    'type' => 'relation',
                    'label' => 'Author',
                    'relation' => 'author',
                    'relationName' => 'name',
                    'relationValue' => 'id',
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('rejects a select column without options', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'status',
                    'type' => 'select',
                    'label' => 'Status',
                ],
            ],
        ]);

        expect($errors)->not->toBeEmpty();
    });

    it('accepts filter overrides without type (inherited from column)', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'created_at',
                    'type' => 'date',
                    'label' => 'Created',
                ],
            ],
            'filters' => [
                'created_at' => [
                    'label' => 'Created',
                    'placeholder' => 'Pick a date',
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts filter select with options', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'status',
                    'type' => 'select',
                    'label' => 'Status',
                    'options' => [
                        ['value' => 'draft', 'label' => 'Draft'],
                    ],
                ],
            ],
            'filters' => [
                'status' => [
                    'type' => 'select',
                    'options' => [
                        ['value' => 'draft', 'label' => 'Draft'],
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts filter select without options', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'status',
                    'type' => 'select',
                    'label' => 'Status',
                    'options' => [
                        ['value' => 'draft', 'label' => 'Draft'],
                    ],
                ],
            ],
            'filters' => [
                'status' => [
                    'type' => 'select',
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts enabled_if on list and bulk actions', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'actions' => [
                'export' => [
                    'label' => 'Export',
                    'action' => 'create',
                    'enabled_if' => [
                        'any' => [
                            ['list.search_present' => true],
                            ['list.filters_applied' => true],
                        ],
                    ],
                ],
            ],
            'bulk_actions' => [
                'archive' => [
                    'label' => 'Archive',
                    'action' => 'delete',
                    'enabled_if' => [
                        'all' => [
                            ['list.selection.min' => 1],
                        ],
                    ],
                ],
            ],
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts visible_if on list and bulk actions', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'actions' => [
                'export' => [
                    'label' => 'Export',
                    'action' => 'create',
                    'visible_if' => [
                        'any' => [
                            ['list.search_present' => true],
                        ],
                    ],
                ],
            ],
            'bulk_actions' => [
                'archive' => [
                    'label' => 'Archive',
                    'action' => 'delete',
                    'visible_if' => [
                        'all' => [
                            ['list.selection.min' => 1],
                        ],
                    ],
                ],
            ],
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('rejects column actions when actions is not an array', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'actions',
                    'type' => 'actions',
                    'label' => 'Actions',
                    'actions' => [
                        'edit' => ['label' => 'Edit', 'action' => 'edit'],
                    ],
                ],
            ],
        ]);

        expect($errors)->not->toBeEmpty();
    });

    it('accepts edit_form_field on list columns', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                    'edit_form_field' => [
                        'type' => 'textarea',
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('rejects edit_form_field without type on list columns', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                    'edit_form_field' => [
                        'placeholder' => 'Write...',
                    ],
                ],
            ],
        ]);

        expect($errors)->not->toBeEmpty();
    });

    it('rejects malformed embedded table column edit_form_field', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'fields' => [
                'items' => [
                    'type' => 'table',
                    'label' => 'Items',
                    'relation' => 'items',
                    'columns' => [
                        [
                            'id' => 'name',
                            'label' => 'Name',
                            'type' => 'text',
                            'edit_form_field' => [
                                'placeholder' => 'Missing type',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        expect($errors)->not->toBeEmpty();
    });

    it('accepts row_click enum values', function () {
        $minimalColumns = [
            [
                'id' => 'title',
                'type' => 'text',
                'label' => 'Title',
            ],
        ];

        expect(CompositionSchemaAsserter::validateList([
            'row_click' => 'none',
            'columns' => $minimalColumns,
        ]))->toBeEmpty();

        expect(CompositionSchemaAsserter::validateList([
            'row_click' => 'edit_page',
            'columns' => $minimalColumns,
        ]))->toBeEmpty();

        expect(CompositionSchemaAsserter::validateList([
            'row_click' => 'edit_modal',
            'columns' => $minimalColumns,
        ]))->toBeEmpty();

        expect(CompositionSchemaAsserter::validateList([
            'row_click' => 'edit_drawer',
            'columns' => $minimalColumns,
        ]))->toBeEmpty();
    });

    it('rejects unknown top-level list keys', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
            'unknown_top_level_key' => true,
        ]);

        expect($errors)->not->toBeEmpty();
    });

    it('rejects unknown nested list keys', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                    'unknown_nested_key' => 'x',
                ],
            ],
        ]);

        expect($errors)->not->toBeEmpty();
    });

    it('rejects unknown top-level form keys', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
            'unknown_top_level_key' => true,
        ]);

        expect($errors)->not->toBeEmpty();
    });

    it('rejects unknown nested form keys', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'label' => 'Title',
                    'unknown_nested_key' => true,
                ],
            ],
        ]);

        expect($errors)->not->toBeEmpty();
    });

    it('accepts list-level default_sort', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'published_at',
                    'type' => 'date',
                    'label' => 'Published At',
                    'sortable' => true,
                ],
            ],
            'default_sort' => [
                'key' => 'published_at',
                'direction' => 'desc',
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts embedded table field default_sort', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'fields' => [
                'items' => [
                    'type' => 'table',
                    'label' => 'Items',
                    'relation' => 'items',
                    'columns' => [
                        'title' => [
                            'label' => 'Title',
                        ],
                    ],
                    'default_sort' => [
                        'key' => 'title',
                        'direction' => 'asc',
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts model-backed embedded table field', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'fields' => [
                'items' => [
                    'type' => 'table',
                    'label' => 'Items',
                    'model' => 'Flatpack\\Tests\\Models\\Post',
                    'columns' => [
                        'title' => [
                            'label' => 'Title',
                        ],
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('rejects embedded table field with both model and relation', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'fields' => [
                'items' => [
                    'type' => 'table',
                    'label' => 'Items',
                    'model' => 'Flatpack\\Tests\\Models\\Post',
                    'relation' => 'items',
                    'columns' => [
                        'title' => [
                            'label' => 'Title',
                        ],
                    ],
                ],
            ],
        ]);

        expect($errors)->not->toBeEmpty();
    });

    it('rejects embedded table field provider source', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'fields' => [
                'items' => [
                    'type' => 'table',
                    'label' => 'Items',
                    'provider' => 'items_provider',
                    'columns' => [
                        'title' => [
                            'label' => 'Title',
                        ],
                    ],
                ],
            ],
        ]);

        expect($errors)->not->toBeEmpty();
    });
});

describe('widget schema contracts', function () {
    it('accepts root-level widgets in list composition', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
            'widgets' => [
                'revenue' => [
                    'type' => 'metric',
                    'provider' => 'total_revenue',
                    'label' => 'Total Revenue',
                    'value_format' => [
                        'kind' => 'currency',
                        'currency' => 'USD',
                    ],
                    'period' => [
                        'kind' => 'month',
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts form widget field with nested widget map', function () {
        $errors = CompositionSchemaAsserter::validateForm([
            'fields' => [
                'total_revenue_widget' => [
                    'type' => 'widget',
                    'label' => 'Total Revenue',
                    'helperText' => 'Revenue for the last 6 months',
                    'widget' => [
                        'total_revenue' => [
                            'type' => 'metric',
                            'provider' => 'demo_total_revenue',
                            'label' => 'Total Revenue',
                            'description' => 'Revenue trend for the last 6 months',
                            'value_format' => [
                                'kind' => 'currency',
                                'currency' => 'USD',
                                'maximumFractionDigits' => 2,
                            ],
                            'period' => [
                                'kind' => 'month',
                                'lookback' => 6,
                                'label' => 'this month',
                            ],
                            'trend' => [
                                'precision' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('rejects metric widget without provider in list widgets', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
            'widgets' => [
                'revenue' => [
                    'type' => 'metric',
                    'label' => 'Total Revenue',
                    'value_format' => [
                        'kind' => 'currency',
                        'currency' => 'USD',
                    ],
                    'period' => [
                        'kind' => 'month',
                    ],
                ],
            ],
        ]);

        expect($errors)->not->toBeEmpty();
    });

    it('accepts chart widget in list composition', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
            'widgets' => [
                'traffic' => [
                    'type' => 'chart',
                    'provider' => 'traffic_area_chart',
                    'label' => 'Traffic',
                    'chart' => [
                        'mode' => 'bar',
                        'x_key' => 'date',
                        'variant' => 'area_stacked',
                        'series' => [
                            ['key' => 'desktop', 'label' => 'Desktop'],
                            ['key' => 'mobile', 'label' => 'Mobile'],
                        ],
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('accepts table widget with provider xor model', function () {
        $providerErrors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
            'widgets' => [
                'posts_table' => [
                    'type' => 'table',
                    'provider' => 'posts_table_provider',
                    'label' => 'Posts',
                    'columns' => [
                        'title' => [
                            'label' => 'Title',
                            'searchable' => true,
                            'sortable' => true,
                        ],
                    ],
                ],
            ],
        ]);

        $modelErrors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
            'widgets' => [
                'posts_table' => [
                    'type' => 'table',
                    'model' => 'Flatpack\\Tests\\Models\\Post',
                    'label' => 'Posts',
                    'columns' => [
                        'title' => [
                            'label' => 'Title',
                            'searchable' => true,
                            'sortable' => true,
                        ],
                        'status' => [
                            'type' => 'select',
                            'label' => 'Status',
                            'options' => [
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ],
                        ],
                    ],
                    'default_sort' => [
                        'key' => 'title',
                        'direction' => 'asc',
                    ],
                ],
            ],
        ]);

        expect($providerErrors)->toBeEmpty()
            ->and($modelErrors)->toBeEmpty();
    });

    it('accepts table widget without label', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
            'widgets' => [
                'recent_posts' => [
                    'type' => 'table',
                    'provider' => 'recent_posts',
                    'columns' => [
                        'title' => [
                            'label' => 'Title',
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ]);

        expect($errors)->toBeEmpty();
    });

    it('rejects table widget when neither or both provider and model are set', function () {
        $neitherErrors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
            'widgets' => [
                'posts_table' => [
                    'type' => 'table',
                    'label' => 'Posts',
                    'columns' => [
                        'title' => [
                            'label' => 'Title',
                        ],
                    ],
                ],
            ],
        ]);

        $bothErrors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
            'widgets' => [
                'posts_table' => [
                    'type' => 'table',
                    'provider' => 'posts_table_provider',
                    'model' => 'Flatpack\\Tests\\Models\\Post',
                    'label' => 'Posts',
                    'columns' => [
                        'title' => [
                            'label' => 'Title',
                        ],
                    ],
                ],
            ],
        ]);

        expect($neitherErrors)->not->toBeEmpty()
            ->and($bothErrors)->not->toBeEmpty();
    });

    it('rejects table widget when columns is missing', function () {
        $errors = CompositionSchemaAsserter::validateList([
            'columns' => [
                [
                    'id' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
            'widgets' => [
                'posts_table' => [
                    'type' => 'table',
                    'provider' => 'posts_table_provider',
                    'label' => 'Posts',
                ],
            ],
        ]);

        expect($errors)->not->toBeEmpty();
    });
});
