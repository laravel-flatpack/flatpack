<?php

use Flatpack\View\Components\FormField;

it('creates a form field component with default options', function () {
    $field = new FormField('title', 'posts', \Flatpack\Tests\Models\Post::class, [
        'label' => 'Title',
        'placeholder' => 'Enter a title',
    ]);

    $this->assertEquals('posts', $field->entity);
    $this->assertEquals(\Flatpack\Tests\Models\Post::class, $field->model);

    $this->assertEquals('text', $field->type);
    $this->assertEquals('full', $field->span);
    $this->assertEquals(false, $field->required);
    $this->assertEquals(false, $field->readonly);
    $this->assertEquals('Title', $field->label);
    $this->assertEquals('Enter a title', $field->placeholder);
});

it('creates a form field component for relations', function () {
    $field = new FormField('category', 'posts', \Flatpack\Tests\Models\Post::class, [
        'label' => 'Category',
        'type' => 'relation',
        'relation' => 'category',
    ]);

    $this->assertEquals('posts', $field->entity);
    $this->assertEquals(\Flatpack\Tests\Models\Post::class, $field->model);

    $this->assertEquals('category', $field->relation);
});

it('creates a form field component with disabled relationship', function () {
    $field = new FormField('category', 'posts', \Flatpack\Tests\Models\Post::class, [
        'label' => 'Category',
        'type' => 'relation',
    ]);

    $this->assertEquals('posts', $field->entity);
    $this->assertEquals(\Flatpack\Tests\Models\Post::class, $field->model);

    $this->assertEquals(false, $field->relation);
});

it('returns the error messages for the field', function () {
    $field = new FormField('title', 'posts', \Flatpack\Tests\Models\Post::class, [
        'label' => 'Title',
        'placeholder' => 'Enter a title',
    ]);

    $errors = new \Illuminate\Support\ViewErrorBag();
    $errors->put('default', new \Illuminate\Support\MessageBag([
        'title' => ['The title field is required.'],
        'email' => ['The email field is required.']
    ]));

    $this->assertEquals(
        ['The title field is required.'],
        $field->getErrorMessages($errors)
    );
});
