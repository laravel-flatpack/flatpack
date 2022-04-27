<?php

use Flatpack\Actions\EmptyTrash;

beforeEach(function () {
    $this->actionInstance = new EmptyTrash('posts', \Flatpack\Tests\Models\Post::class);
});

it('returns the successful message', function () {
    $this->expect($this->actionInstance->getMessage())
         ->toBe('posts permanently deleted.');
});

it('returns the confirmation message', function () {
    $this->expect($this->actionInstance->getConfirmationMessage())
         ->toBe('Are you sure you want to permanently delete these posts?');
});

it('throws an error if the model is not compatible', function () {
    $action = new EmptyTrash('categories', \Flatpack\Tests\Models\Category::class);

    $this->expectException(\Flatpack\Exceptions\ActionNotFoundException::class);

    $action->run();
});

it('handles the action', function () {
    \Flatpack\Tests\Models\Post::insert([
        ['id' => 101, 'title' => 'Lorem ipsum'],
        ['id' => 102, 'title' => 'Dolor sit amet'],
        ['id' => 103, 'title' => 'Lorem ipsum dolor sit amet'],
    ]);

    $this->expect(\Flatpack\Tests\Models\Post::find(101))->not->toBe(null);

    \Flatpack\Tests\Models\Post::whereIn('id', [101, 102, 103])->delete();

    $this->expect(\Flatpack\Tests\Models\Post::withTrashed()->find(101))->not->toBe(null);

    $this->actionInstance->run();

    $this->expect(\Flatpack\Tests\Models\Post::withTrashed()->find(101))->toBe(null);
    $this->expect(\Flatpack\Tests\Models\Post::withTrashed()->find(102))->toBe(null);
    $this->expect(\Flatpack\Tests\Models\Post::withTrashed()->find(103))->toBe(null);
});
