<?php

use Flatpack\View\Components\RelationField;

it('creates a relation field component with options', function () {
    $options = [
        'label' => 'Category',
        'placeholder' => 'Select a category',
        'type' => 'relation',
        'relation' => [
            'name' => 'category',
            'display' => 'name',
        ]
    ];
    $entry = new \Flatpack\Tests\Models\Post();

    $field = new RelationField(
        'category',
        $options,
        'posts',
        \Flatpack\Tests\Models\Post::class,
        $entry
    );

    $this->assertEquals('posts', $field->entity);
    $this->assertEquals(\Flatpack\Tests\Models\Post::class, $field->model);

    $this->assertEquals('relation', $field->type);
    $this->assertEquals(false, $field->required);
    $this->assertEquals(false, $field->readonly);
    $this->assertEquals('Category', $field->label);
    $this->assertEquals('Select a category', $field->placeholder);
});

it('creates a relation field component with create form', function () {
    $options = [
        'label' => 'Tags',
        'placeholder' => 'Select a tag',
        'type' => 'relation',
        'relation' => [
            'name' => 'tags',
            'display' => 'name',
            'create' => true,
            'fields' => [
                'name' => [
                    'label' => 'Name',
                    'type' => 'text',
                ],
            ]
        ]
    ];

    $entry = new \Flatpack\Tests\Models\Post();

    $field = new RelationField(
        'tags',
        $options,
        'posts',
        \Flatpack\Tests\Models\Post::class,
        $entry
    );

    $this->assertEquals('posts', $field->entity);
    $this->assertEquals(\Flatpack\Tests\Models\Post::class, $field->model);

    $this->assertEquals('relation', $field->type);
    $this->assertEquals([
        'name' => [
            'label' => 'Name',
            'type' => 'text',
        ],
    ], $field->formFields);
});


it('creates a relation field component with disabled relationship', function () {
    $options = [
        'label' => 'Tags',
        'type' => 'relation'
    ];

    $entry = new \Flatpack\Tests\Models\Post();

    $field = new RelationField(
        'not-a-relation',
        $options,
        'posts',
        \Flatpack\Tests\Models\Post::class,
        $entry
    );

    $this->assertEquals('relation', $field->type);
    $this->assertEquals(false, $field->relation);
});
