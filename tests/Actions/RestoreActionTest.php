<?php

use Flatpack\Actions\Restore;

beforeEach(function () {
    $this->actionInstance = new Restore('posts', \Flatpack\Tests\Models\Post::class);
});

it('returns the successful message', function () {
    $this->expect($this->actionInstance->getMessage())
         ->toBe('post restored.');
});

it('returns the successful message for bulk action', function () {
    $this->expect($this->actionInstance->setSelectedKeys([1, 2, 3])->getMessage())
         ->toBe('3 posts restored.');
});

it('returns the confirmation message', function () {
    $this->expect($this->actionInstance->getConfirmationMessage())
         ->toBe('Are you sure you want to restore the selected posts?');

    $this->expect($this->actionInstance->setSelectedKeys([1, 2, 3])->getConfirmationMessage())
         ->toBe('Are you sure you want to restore the selected posts?');
});

it('handles the action', function () {
    \Flatpack\Tests\Models\Post::insert([
        ['id' => 101, 'title' => 'Lorem ipsum'],
        ['id' => 102, 'title' => 'Dolor sit amet'],
        ['id' => 103, 'title' => 'Lorem ipsum dolor sit amet'],
    ]);

    \Flatpack\Tests\Models\Post::whereIn('id', [101, 102, 103])->delete();

    $this->expect(\Flatpack\Tests\Models\Post::find(101))->toBe(null);
    $this->expect(\Flatpack\Tests\Models\Post::find(102))->toBe(null);
    $this->expect(\Flatpack\Tests\Models\Post::find(103))->toBe(null);

    $this->actionInstance->setSelectedKeys([101, 102, 103])->run();

    $this->expect(\Flatpack\Tests\Models\Post::find(101))->not->toBe(null);
    $this->expect(\Flatpack\Tests\Models\Post::find(102))->not->toBe(null);
    $this->expect(\Flatpack\Tests\Models\Post::find(103))->not->toBe(null);
});


it('throws an error if the model is not compatible', function () {
    $action = new Restore('categories', \Flatpack\Tests\Models\Category::class);

    $this->expectException(\Flatpack\Exceptions\ActionNotFoundException::class);

    $action->run();
});
