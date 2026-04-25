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
});
