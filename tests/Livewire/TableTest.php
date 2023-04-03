<?php

use Flatpack\Http\Livewire\Table;
use Flatpack\Tests\Models\Post;
use Livewire\Livewire;

it('displays posts in table component', function () {
    Post::create(['title' => 'Post title 1']);
    Post::create(['title' => 'Post title 2']);
    Post::create(['title' => 'Post title 3']);

    Livewire::test(Table::class, [
        'entity' => 'posts',
        'model' => Post::class,
        'composition' => [
            'bulk' => [
                'delete' => [
                    'label' => 'Delete',
                    'action' => 'delete',
                    'confirm' => true,
                ],
            ],
            'scopes' => [
                'default' => [
                    'label' => 'All',
                ],
                'onlyTrashed' => [
                    'label' => 'Trashed',
                    'count' => false,
                ],
            ],
            'columns' => [
                'image' => [
                    'type' => 'image',
                ],
                'title' => [
                    'lable' => 'title',
                    'searchable' => true,
                    'sortable' => true,
                ],
                'created_at' => [
                    'lable' => 'Created',
                    'type' => 'datetime',
                    'sortable' => true,
                ],
            ],
        ],
    ])
    ->assertSet('bulkActions', [
        'delete' => 'Delete',
    ])
    ->call('render')
    ->assertViewIs('flatpack::components.table')
    ->assertSet('rows', Post::orderBy('id', 'desc')->paginate(10))
    ->call('action', 'non-existing')
    ->assertEmitted('notify', [
        "type" => "error",
        "message" => "Action not found: non-existing",
        "errors" => [],
    ])
    ->set('selectAll', true)
    ->call('bulkAction', 'delete')
    ->assertEmitted('notify', [
        'type' => 'success',
        'message' => '3 posts deleted.',
    ])
    ->set('scope', 'default')
    ->assertSet('rows', Post::where(1, 0)->paginate(10))
    ->set('scope', 'onlyTrashed')
    ->assertSet('rows', Post::onlyTrashed()->orderBy('id', 'desc')->paginate(10))
    ->set('selected', [0, 1, 2])
    ->call('action', 'empty-trash')
    ->assertSet('rows', Post::where(1, 0)->paginate(10));
});

it('search posts in table component', function () {
    Post::create(['title' => 'Post title 1']);
    Post::create(['title' => 'Lorem ipsum']);
    Post::create(['title' => 'Post title 3']);

    Livewire::test(Table::class, [
        'entity' => 'posts',
        'model' => Post::class,
        'composition' => [
            'bulk' => [
                'delete' => [
                    'label' => 'Delete',
                    'action' => 'delete',
                    'confirm' => true,
                ],
            ],
            'columns' => [
                'image' => [
                    'type' => 'image',
                ],
                'title' => [
                    'lable' => 'title',
                    'searchable' => true,
                    'sortable' => true,
                ],
                'status' => [
                    'label' => 'status',
                    'type' => 'boolean',
                ],
                'created_at' => [
                    'lable' => 'Created',
                    'type' => 'datetime',
                    'sortable' => true,
                ],
            ],
        ],
    ])
    ->assertSet('selected', [])
    ->assertSet('selectPage', false)
    ->assertSet('selectAll', false)
    ->assertSet('bulkActions', [
        'delete' => 'Delete',
    ])
    ->set('scope', 'default')
    ->call('render')
    ->assertViewIs('flatpack::components.table')
    ->assertSet('rows', Post::orderBy('id', 'desc')->paginate(10))
    ->set('filters', ['search' => 'lorem ipsum'])
    ->assertSet('filters', [
        'search' => 'lorem ipsum',
    ])
    ->assertSet('rows', Post::where('title', 'LIKE', '%lorem ipsum%')->paginate(10));
});
