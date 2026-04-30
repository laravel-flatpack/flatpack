<?php

declare(strict_types=1);

use Flatpack\Services\Navigation\MenuItem;

test('menu item converts to array', function () {
    $item = new MenuItem('posts', 'Posts', 'flatpack.posts.index', 'book-open');

    expect($item->toArray())->toBe([
        'slug' => 'posts',
        'name' => 'Posts',
        'url' => 'flatpack.posts.index',
        'icon' => 'book-open',
    ]);
});

test('menu item defaults navOrder to 99', function () {
    $item = new MenuItem('posts', 'Posts', '/flatpack/posts', 'folder');

    expect($item->navOrder)->toBe(99);
});

test('menu item accepts explicit navOrder', function () {
    $item = new MenuItem('posts', 'Posts', '/flatpack/posts', 'folder', 7);

    expect($item->navOrder)->toBe(7);
});
