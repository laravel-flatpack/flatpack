<?php

declare(strict_types=1);

use Flatpack\Lists\ListHeaderActions;

test('prefixedUrl joins flatpack prefix and path', function () {
    expect(ListHeaderActions::prefixedUrl('/posts/create', 'flatpack'))
        ->toBe('/flatpack/posts/create');
    expect(ListHeaderActions::prefixedUrl('posts/create', 'admin'))
        ->toBe('/admin/posts/create');
});

test('prefixedUrl returns path only when prefix is empty', function () {
    expect(ListHeaderActions::prefixedUrl('/x', ''))->toBe('/x');
});

test('prefixedUrl leaves absolute URLs unchanged', function () {
    expect(ListHeaderActions::prefixedUrl('https://example.com/p', 'flatpack'))
        ->toBe('https://example.com/p');
    expect(ListHeaderActions::prefixedUrl('//cdn.example/x', 'flatpack'))
        ->toBe('//cdn.example/x');
});
