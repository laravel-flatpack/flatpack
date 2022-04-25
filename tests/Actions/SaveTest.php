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
    $fields = [
        'title' => 'Lorem ipsum',
    ];

    $action = $this->actionInstance
        ->setEntry(new \Flatpack\Tests\Models\Post([
            'title' => 'Lorem ispum',
        ]))
        ->setFields($fields)
        ->run();

    $this->expect($action)->toBe(true);
});
