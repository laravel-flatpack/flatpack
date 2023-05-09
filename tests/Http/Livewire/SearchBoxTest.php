<?php

use Flatpack\Http\Livewire\SearchBox;
use Flatpack\Tests\Models\Category;
use Flatpack\Tests\Models\Post;
use Livewire\Livewire;

beforeEach(function () {
    Post::factory()->create(['title' => 'Lorem ipsum.']);
    Post::factory()->create(['title' => 'Lorem ipsum dolor sit amet.']);
    Post::factory()->create(['title' => 'Sit amet lorem ipsum.']);
    Post::factory()->create(['title' => 'Asperiores fugiat.']);
    Category::factory()->create(['name' => 'Lorem ipsum...', 'slug' => 'lorem-ipsum']);
});

it('renders the search box component', function () {
    Livewire::test(SearchBox::class)
        ->call('render')
        ->assertViewIs('flatpack::components.search-box');
    ;
});

it('handles search results', function () {
    Livewire::test(SearchBox::class)
        ->call('render')
        ->assertSet('showDropdown', true)
        ->set('search', 'lor')
        ->assertSet('highlightIndex', -1)
        ->call('incrementHighlight')
        ->assertSet('highlightIndex', 0)
        ->call('incrementHighlight')
        ->assertSet('highlightIndex', 1)
        ->call('decrementHighlight')
        ->assertSet('highlightIndex', 0)
        ->call('incrementHighlight')
        ->call('incrementHighlight')
        ->call('incrementHighlight')
        ->assertSet('highlightIndex', 0)
        ->call('decrementHighlight')
        ->assertSet('highlightIndex', 2)
        ->call('selectResult')
        ->assertSet('selectedResult', 2)
        ->assertEmitted('redirect')
        ->call('hideDropdown')
        ->assertSet('showDropdown', false)
    ;
});
