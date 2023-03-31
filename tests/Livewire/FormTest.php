<?php

use Flatpack\Http\Livewire\Form;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\Tag;
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

it('throw error if fields validation fails', function () {
    Livewire::test(Form::class, [
        'entity' => 'posts',
        'model' => Post::class,
        'entry' => new Post(),
        'formType' => 'create',
        'composition' => [
            'header' => [
                'title' => [
                    'type' => 'editable',
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
                    'type' => 'textarea',
                    'rules' => 'required|string',
                ],
            ],
        ],
    ])
    ->call('action', 'save')
    ->assertHasErrors();
});

it('creates a new post entry', function () {
    Livewire::test(Form::class, [
        'entity' => 'posts',
        'model' => Post::class,
        'entry' => new Post(),
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
                    'type' => 'textarea',
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
                    'type' => 'textarea',
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
    $this->assertTrue(Post::whereTitle('Lorem ipsum')->exists());
});

it('handles form field components state', function () {
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
                'image' => [
                    'type' => 'image-upload',
                ],
            ],
        ],
    ])
    ->call('saveEditorState', 'body', ['foo' => 'bar'])
    ->assertSet('fields', [
        'title' => 'Testing title',
        'body' => json_encode(['foo' => 'bar']),
        'image' => null,
    ])
    ->call('saveImageUploaderState', 'image', ['img1', 'img2', 'img3'])
    ->assertSet('fields', [
        'title' => 'Testing title',
        'body' => json_encode(['foo' => 'bar']),
        'image' => ['img1', 'img2', 'img3'],
    ]);
});

it('creates a new related entry', function () {
    $post = Post::create([
        'title' => 'Lorem ipsum',
    ]);

    $component = Livewire::test(Form::class, [
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
                'tags' => [
                    'type' => 'tag-input',
                    'placeholder' => 'Write tags...',
                    'relation' => [
                        'name' => 'tags',
                        'display' => 'name',
                        'make' => true,
                    ],
                ],
            ],
        ],
    ]);

    $component
        ->set('fields', [
            'title' => 'Lorem ipsum',
            'body' => 'Lorem ipsum dolor sit amet.',
        ])
        ->call('createRelatedEntity', 'tags', 'testing-new-tag')
        ->assertEmitted('flatpack-form:related-entity-created:tags', 1)
        ->call('createRelatedEntity', 'tags', 'another-tag')
        ->assertEmitted('flatpack-form:related-entity-created:tags', 2);

    $this->assertTrue(Tag::whereName('testing-new-tag')->exists());
    $this->assertTrue(Tag::whereName('another-tag')->exists());

    $component
        ->call('saveTagInputState', 'tags', '1,2')
        ->call('action', 'save');

    $tags = $post->refresh()->tags->pluck('name');

    $this->assertTrue($tags->contains('testing-new-tag'));
    $this->assertTrue($tags->contains('another-tag'));
});


it('handles form failures and error messages', function () {
    $post = Post::create([
        'title' => 'Lorem ipsum',
    ]);

    $component = Livewire::test(Form::class, [
        'entity' => 'posts',
        'model' => Post::class,
        'entry' => $post,
        'formType' => 'edit',
        'composition' => [
            'header' => [
                'title' => [
                    'type' => 'editable',
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
                'tags' => [
                    'type' => 'tag-input',
                    'relation' => [
                        'name' => 'tags',
                        'display' => 'not-existing',
                        'create' => true,
                    ],
                ],
            ],
        ],
    ]);

    $component
        ->call('createRelatedEntity', 'tags', 'foo')
        ->assertEmitted('notify', [
            'type' => 'error',
            'message' => 'Failed to create new tags',
            'errors' => [],
        ]);

    $component
        ->call('showImageUploaderError', 'Error Message', [])
        ->assertEmitted('notify', [
            'type' => 'error',
            'message' => 'Error Message',
            'errors' => [],
        ]);
});
