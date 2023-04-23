<?php

use Flatpack\Http\Livewire\Table;
use Flatpack\Tests\Models\Post;
use Livewire\Livewire;

it('displays posts in table component', function () {
    Post::create(['title' => 'Post title 1', 'status' => 3 ]);
    Post::create(['title' => 'Post title 2', 'status' => 4 ]);
    Post::create(['title' => 'Post title 3', 'status' => 4 ]);
    Post::create(['title' => 'Post title 4', 'status' => 3 ]);
    Post::create(['title' => 'Post title 5', 'status' => 2 ]);
    Post::create(['title' => 'Post title 6', 'status' => 1 ]);

    Livewire::test(Table::class, [
        'entity' => 'posts',
        'model' => Post::class,
        'composition' => [
            'toolbar' => [
                'create' => [
                    'label' => 'New Post',
                    'icon' => 'plus',
                    'link' => 'create',
                    'style' => 'primary',
                ],
            ],
            'bulk' => [
                'delete' => [
                    'label' => 'Delete',
                    'action' => 'delete',
                    'confirm' => true,
                ],
            ],
            'filters' => [
                'status' => [
                    'type' => 'multiselect',
                    'label' => 'Status',
                    'options' => [
                        '1' => 'Draft',
                        '2' => 'Private',
                        '3' => 'Published',
                        '4' => 'Scheduled',
                    ],
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
                    'invisible' => true,
                ],
                'updated_at' => [
                    'lable' => 'Updated',
                    'type' => 'datetime',
                    'sortable' => true,
                ],
            ],
        ],
    ])
    ->assertSet('bulkActions', [
        'delete' => 'Delete',
    ])
    ->assertSet('table.filters', [
        'status' => [],
    ])
    ->call('render')
    ->assertViewIs('flatpack::components.table')
    ->assertSee([
        'Post title 1',
        'Post title 2',
        'Post title 3',
        'Post title 4',
        'Post title 5',
        'Post title 6',
    ])
    ->set('table.filters.status', [3,4])
    ->assertSee([
        'Post title 1',
        'Post title 2',
        'Post title 3',
        'Post title 4',
    ])
    ->set('selected', [1])
    ->call('bulkAction', 'delete')
    ->assertDontSee('Post title 1')
    ->assertSet('selected', []);
});

it('search posts in table component', function () {
    Post::create(['title' => 'Post title 1']);
    Post::create(['title' => 'Post title 2', 'status' => 3 ]);
    Post::create(['title' => 'Post title 3']);
    Post::create(['title' => 'Lorem Ipsum']);
    Post::create(['title' => 'Post title 5']);
    Post::create(['title' => 'Post title 6']);

    Livewire::test(Table::class, [
        'entity' => 'posts',
        'model' => Post::class,
        'composition' => [
            'filters' => [
                'id' => [
                    'label' => 'ID',
                    'placeholder' => 'Record ID',
                    'type' => 'number',
                ],
                'title' => [
                    'label' => 'Title',
                    'maxlength' => 100,
                ],
                'status' => [
                    'type' => 'select',
                    'label' => 'Status',
                    'placeholder' => 'Select a status',
                    'options' => [
                        '1' => 'Draft',
                        '2' => 'Private',
                        '3' => 'Published',
                        '4' => 'Scheduled',
                    ],
                ],
                'categories' => [
                    'type' => 'relation',
                    'label' => 'Categories',
                    'placeholder' => 'Select categories',
                ],
                'created_at' => [
                    'type' => 'date',
                    'label' => 'Created',
                    'placeholder' => 'Select a date',
                ],
            ],
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
                    'format' => 'Y-m-d H:i:s',
                ],
            ],
        ],
    ])
    ->assertSet('table.filters', [
        'id' => null,
        'status' => null,
        'created_at' => null,
        'categories' => [],
        'title' => null,
    ])
    ->assertSet('selected', [])
    ->assertSet('selectPage', false)
    ->assertSet('selectAll', false)
    ->assertSet('bulkActions', [
        'delete' => 'Delete',
    ])
    ->call('render')
    ->assertViewIs('flatpack::components.table')
    ->set('table.filters.id', '2')
    ->assertSee('Post title 2')
    ->assertDontSee([
        'Post title 3',
        'Post title 4',
        'Post title 5',
        'Post title 6',
    ])
    ->call('setFilterDefaults')
    ->set('table.filters.categories', [2])
    ->assertDontSee([
        'Post title 1',
        'Post title 2',
        'Post title 3',
        'Post title 4',
        'Post title 5',
        'Post title 6',
    ])
    ->call('setFilterDefaults')
    ->set('table.filters.title', 'Post title 2')
    ->set('table.filters.status', 3)
    ->assertSee('Post title 2')
    ->call('setFilterDefaults')
    ->set('table.filters.created_at', date('Y-m-d'))
    ->assertSee('Post title 2')
    ->call('setFilterDefaults');
});
