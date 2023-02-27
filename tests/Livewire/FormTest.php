<?php

use Flatpack\Http\Livewire\Form;
use Flatpack\Tests\Models\Post;
use Livewire\Livewire;

it('redirects back to index list', function () {
    Livewire::test(Form::class, [
        'entity' => 'posts',
        'model' => Post::class,
        'entry' => new Post(),
        'formType' => 'create',
    ])
    ->call('action', 'cancel')
    ->assertRedirect('/backend/posts');
});

it('displays an error if action does not exist', function () {
    Livewire::test(Form::class, [
        'entity' => 'posts',
        'model' => Post::class,
        'entry' => new Post(),
        'formType' => 'create',
    ])
    ->call('action', 'not-existing')
    ->assertEmitted('notify', [
        'type' => 'error',
        'message' => 'Action not found: not-existing',
        'errors' => [],
    ]);
});

it('creates a new post entry', function () {
    $post = new Post();
    Livewire::test(Form::class, [
        'entity' => 'posts',
        'model' => Post::class,
        'entry' => $post,
        'formType' => 'create',
        'composition' => [
            'header' => [
                'title' => [
                    'type' => 'editable',
                    'size' => 'large',
                    'placeholder' => 'Your post title...',
                    'rules' => 'required|string',
                ],
            ],
            'toolbar' => [
                'save' => [
                    'label' => 'Save',
                    'action' => 'save',
                    'redirect' => true,
                ],
            ],
            'main' => [
                'body' => [
                    'type' => 'block-editor',
                    'rules' => 'required',
                ],
            ],
        ],
    ])
    ->set('fields', [
        'title' => 'Lorem ipsum',
        'body' => 'Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet.',
    ])
    ->call('action', 'save', [
        'redirect' => true,
    ])
    ->assertEmitted('redirect');

    $this->assertTrue(Post::whereTitle('Lorem ipsum')->exists());
});

it('updates an existing post entry', function () {
    $post = Post::create([
        'title' => 'Testing title',
    ]);

    $this->assertTrue(Post::whereTitle('Testing title')->exists());

    Livewire::test(Form::class, [
        'entity' => 'posts',
        'model' => Post::class,
        'entry' => $post,
        'formType' => 'edit',
        'composition' => [
            'header' => [
                'title' => [
                    'type' => 'editable',
                    'size' => 'large',
                    'placeholder' => 'Your post title...',
                    'rules' => 'required|string',
                ],
            ],
            'toolbar' => [
                'save' => [
                    'label' => 'Save',
                    'action' => 'save',
                ],
            ],
            'main' => [
                'body' => [
                    'type' => 'block-editor',
                    'rules' => 'required',
                ],
            ],
        ],
    ])
    ->set('fields', [
        'title' => 'Lorem ipsum',
        'body' => 'Lorem ipsum dolor sit amet.',
    ])
    ->call('action', 'save');

    $this->assertFalse(Post::whereTitle('Testing title')->exists());
});
