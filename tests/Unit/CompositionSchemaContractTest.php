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

    it('accepts row_click_edit false, true, or a column name string', function () {
        $minimalColumns = [
            [
                'id' => 'title',
                'type' => 'text',
                'label' => 'Title',
            ],
        ];

        expect(CompositionSchemaAsserter::validateList([
            'row_click_edit' => false,
            'columns' => $minimalColumns,
        ]))->toBeEmpty();

        expect(CompositionSchemaAsserter::validateList([
            'row_click_edit' => true,
            'columns' => $minimalColumns,
        ]))->toBeEmpty();

        expect(CompositionSchemaAsserter::validateList([
            'row_click_edit' => 'uuid',
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
});
