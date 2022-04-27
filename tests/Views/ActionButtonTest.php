<?php

use Flatpack\View\Components\ActionButton;

it('creates a button component with the given options', function () {
    $button = new ActionButton('save', 'posts', \Flatpack\Tests\Models\Post::class, [
        'label' => 'Save',
        'confirm' => true,
        'confirmationMessage' => 'Are you sure?',
        'action' => 'save',
        'style' => 'primary',
    ]);

    $this->assertEquals('posts', $button->entity);
    $this->assertEquals(\Flatpack\Tests\Models\Post::class, $button->model);

    $this->assertEquals('Save', $button->label);
    $this->assertEquals(true, $button->confirm);
    $this->assertEquals('Are you sure?', $button->confirmationMessage);
    $this->assertEquals('save', $button->action);
    $this->assertEquals('primary', $button->style);
});

it('creates a button component with confirmation message defined in actions', function () {
    $button = new ActionButton('save', 'posts', \Flatpack\Tests\Models\Post::class, [
        'label' => 'Delete',
        'confirm' => true,
        'action' => 'delete',
        'style' => 'danger',
    ]);

    $this->assertEquals('posts', $button->entity);
    $this->assertEquals(\Flatpack\Tests\Models\Post::class, $button->model);

    $this->assertEquals('Delete', $button->label);
    $this->assertEquals(true, $button->confirm);
    $this->assertEquals('Are you sure you want to delete this post?', $button->confirmationMessage);
    $this->assertEquals('delete', $button->action);
    $this->assertEquals('danger', $button->style);
});

it('creates a button component for bulk actions', function () {
    $button = new ActionButton('save', 'posts', \Flatpack\Tests\Models\Post::class, [
        'label' => 'Delete',
        'confirm' => true,
        'action' => 'delete',
        'style' => 'danger',
    ], 'bulkAction');

    $this->assertEquals('posts', $button->entity);
    $this->assertEquals(\Flatpack\Tests\Models\Post::class, $button->model);

    $this->assertEquals('Delete', $button->label);
    $this->assertEquals(true, $button->confirm);
    $this->assertEquals('Are you sure you want to delete the selected posts?', $button->confirmationMessage);
    $this->assertEquals('delete', $button->action);
    $this->assertEquals('danger', $button->style);
});

it('creates a button component for links', function () {
    $button = new ActionButton('save', 'posts', \Flatpack\Tests\Models\Post::class, [
        'label' => 'New Post',
        'link' => 'create',
        'style' => 'primary',
        'shortcut' => 'enter',
    ], 'bulkAction');

    $this->assertEquals('posts', $button->entity);
    $this->assertEquals(\Flatpack\Tests\Models\Post::class, $button->model);

    $this->assertEquals('New Post', $button->label);
    $this->assertEquals(false, $button->confirm);
    $this->assertNull($button->action);
    $this->assertEquals('http://localhost/backend/posts/create', $button->link);
});

it('creates a button component for links to urls', function () {
    $button = new ActionButton('save', 'posts', \Flatpack\Tests\Models\Post::class, [
        'label' => 'View Site',
        'link' => '/path/to/url',
    ], 'bulkAction');

    $this->assertEquals('posts', $button->entity);
    $this->assertEquals(\Flatpack\Tests\Models\Post::class, $button->model);

    $this->assertEquals('/path/to/url', $button->link);
});
