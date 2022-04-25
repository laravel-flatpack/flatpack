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
    $action = $this->actionInstance
        ->setEntry(new \Flatpack\Tests\Models\Post([
            'title' => 'Lorem ispum',
        ]))
        ->setFields([
            'title' => 'Lorem ipsum'
        ])
        ->run();

    $this->expect($action)->toBe(true);
});
