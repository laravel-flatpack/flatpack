<?php

use Flatpack\Actions\Save;

beforeEach(function () {
    $this->actionInstance = new Save('posts', \Flatpack\Tests\Models\Post::class);
});

it('returns the successful message', function () {
    $this->expect($this->actionInstance->getMessage())
         ->toBe('post saved.');
});

it('handles the action', function () {
    \Flatpack\Tests\Models\Category::insert([
        ['id' => 1, 'name' => 'Category 1', 'slug' => 'category-1'],
        ['id' => 2, 'name' => 'Category 2', 'slug' => 'category-2'],
        ['id' => 3, 'name' => 'Category 3', 'slug' => 'category-3'],
    ]);

    \Flatpack\Tests\Models\Tag::insert([
        ['id' => 1, 'name' => 'Tag 1'],
        ['id' => 2, 'name' => 'Tag 2'],
        ['id' => 3, 'name' => 'Tag 3'],
    ]);

    $fields = [
        'title' => 'Lorem ipsum',
        'category' => 2,
        'tags' => [
            2, 3,
        ],
    ];

    $entry = new \Flatpack\Tests\Models\Post();
    $entry->title = 'Lorem ipsum';

    $action = $this->actionInstance
        ->setEntry($entry)
        ->setFields($fields)
        ->run();

    $this->expect($this->actionInstance->getEntry())->toBe($entry);
    $this->expect($this->actionInstance->getFields())->toBe($fields);
    $this->expect($this->actionInstance->isSuccess())->toBe(true);
    $this->expect($action)->toBe(true);
});
