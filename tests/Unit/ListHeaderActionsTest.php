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
    expect(ListHeaderActions::prefixedUrl('https://example.com/p', 'flatpack', true))
        ->toBe('https://example.com/p');
});

test('prefixedUrl rejects unsafe or external URLs by default', function () {
    expect(ListHeaderActions::prefixedUrl('javascript:alert(1)', 'flatpack'))->toBe('');
    expect(ListHeaderActions::prefixedUrl('data:text/html;base64,abc', 'flatpack'))->toBe('');
    expect(ListHeaderActions::prefixedUrl('//cdn.example/x', 'flatpack'))->toBe('');
    expect(ListHeaderActions::prefixedUrl('https://evil.example/p', 'flatpack'))->toBe('');
});

test('prefixedUrl allows external URLs when explicitly enabled', function () {
    expect(ListHeaderActions::prefixedUrl('https://evil.example/p', 'flatpack', true))
        ->toBe('https://evil.example/p');
});
