<?php

use Flatpack\View\Components\TagInput;

beforeEach(function () {
    $options = [
        'label' => 'Tags',
        'placeholder' => 'Type a tag...',
        'type' => 'tag-input',
        'relation' => [
            'name' => 'tags',
            'display' => 'name',
        ]
    ];

    $entry = new \Flatpack\Tests\Models\Post();

    $this->field = new TagInput(
        'tags',
        $options,
        'posts',
        \Flatpack\Tests\Models\Post::class,
        $entry
    );
});

it('creates a tag input field component', function () {
    $this->assertEquals('posts', $this->field->entity);
    $this->assertEquals(\Flatpack\Tests\Models\Post::class, $this->field->model);

    $this->assertEquals('tag-input', $this->field->type);
    $this->assertEquals(false, $this->field->required);
    $this->assertEquals(false, $this->field->readonly);
    $this->assertEquals('Tags', $this->field->label);
    $this->assertEquals('Type a tag...', $this->field->placeholder);
});
