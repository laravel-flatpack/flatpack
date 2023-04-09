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
    ->assertViewIs('flatpack::components.table');
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
                    'width' => '50px',
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
    ->call('render')
    ->assertViewIs('flatpack::components.table');
});
