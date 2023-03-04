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
    ]);
});
