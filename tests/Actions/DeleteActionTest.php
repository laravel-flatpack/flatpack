<?php

use Flatpack\Actions\Delete;

beforeEach(function () {
    $this->actionInstance = new Delete('posts', \Flatpack\Tests\Models\Post::class);
});

it('returns the successful message', function () {
    $this->expect($this->actionInstance->getMessage())
         ->toBe('post deleted.');
});

it('returns the successful message for bulk action', function () {
    $this->expect($this->actionInstance->setSelectedKeys([1,2,3])->getMessage())
         ->toBe('3 posts deleted.');
});

it('returns the confirmation message', function () {
    $this->expect($this->actionInstance->getConfirmationMessage())
         ->toBe('Are you sure you want to delete this post?');
});

it('returns the confirmation message for bulk action', function () {
    $this->expect($this->actionInstance->setSelectedKeys([1,2,3])->getConfirmationMessage())
         ->toBe('Are you sure you want to delete the selected posts?');
});

it('handles the action for single entry', function () {
    $entry = \Flatpack\Tests\Models\Post::create([
        'title' => 'Lorem ispum',
    ]);

    $this->actionInstance
         ->setEntry($entry)
         ->setRedirect(true)
         ->run();

    $this->expect($this->actionInstance->getEntry())->toBe($entry);

    $this->expect($this->actionInstance->isSuccess())->toBe(true);

    $this->expect($this->actionInstance->shouldRedirect())->toBe(true);

    $this->expect(\Flatpack\Tests\Models\Post::find($entry->id))->toBe(null);
});

it('handles the action for multiple entries (with soft delete)', function () {
    \Flatpack\Tests\Models\Post::insert([
        ['id' => 101, 'title' => 'Lorem ipsum'],
        ['id' => 102, 'title' => 'Dolor sit amet'],
        ['id' => 103, 'title' => 'Lorem ipsum dolor sit amet'],
    ]);

    $this->actionInstance
         ->setSelectedKeys([101, 102, 103])
         ->run();

    $this->expect(\Flatpack\Tests\Models\Post::find(101))->toBe(null);
    $this->expect(\Flatpack\Tests\Models\Post::find(102))->toBe(null);
    $this->expect(\Flatpack\Tests\Models\Post::find(103))->toBe(null);
});

it('handles the action for multiple entries', function () {
    $actionInstance = new Delete('categories', \Flatpack\Tests\Models\Category::class);

    \Flatpack\Tests\Models\Category::insert([
        ['id' => 101, 'name' => 'Lorem ipsum'],
        ['id' => 102, 'name' => 'Dolor sit amet'],
        ['id' => 103, 'name' => 'Ad nostrum'],
    ]);

    $actionInstance
         ->setSelectedKeys([101, 102, 103])
         ->run();

    $this->expect(\Flatpack\Tests\Models\Category::find(101))->toBe(null);
    $this->expect(\Flatpack\Tests\Models\Category::find(102))->toBe(null);
    $this->expect(\Flatpack\Tests\Models\Category::find(103))->toBe(null);
});
