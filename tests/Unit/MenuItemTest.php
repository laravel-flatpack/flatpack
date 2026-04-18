<?php

declare(strict_types=1);

use Flatpack\Menu\MenuItem;

test('menu item converts to array', function () {
    $item = new MenuItem('posts', 'Posts', 'flatpack.posts.index', 'book-open');

    expect($item->toArray())->toBe([
        'slug' => 'posts',
        'name' => 'Posts',
        'url' => 'flatpack.posts.index',
        'icon' => 'book-open',
    ]);
});
