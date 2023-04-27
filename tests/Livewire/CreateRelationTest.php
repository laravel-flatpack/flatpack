<?php

use Flatpack\Http\Livewire\CreateRelation;
use Flatpack\Tests\Models\Category;
use Livewire\Livewire;

it('renders create relation component', function () {
    Livewire::test(CreateRelation::class, [
        'composition' => [
            'fields' => [
                'name' => [
                    'label' => 'Name',
                ],
                'slug' => [
                    'label' => 'Slug',
                ],
            ],
        ],
        'model' => Category::class,
    ])
        ->call('render')
        ->assertViewIs('flatpack::components.create-relation');
});

it('saves relation form fields', function () {
    Livewire::test(CreateRelation::class, [
        'composition' => [
            'fields' => [
                'name' => [
                    'label' => 'Name',
                ],
                'slug' => [
                    'label' => 'Slug',
                ],
            ],
        ],
        'model' => Category::class,
    ])
    ->set('fields', [
        'name' => 'Lorem ipsum',
        'slug' => 'lorem-ipsum',
    ])
    ->call('submit')
    ->assertEmittedUp('flatpack-relation:updated')
    ->assertDispatchedBrowserEvent('close-modal')
    ->call('cancel')
    ->assertDispatchedBrowserEvent('close-modal');
});
