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

test('sanitizeHref keeps relative paths without prefixing', function () {
    expect(ListHeaderActions::sanitizeHref('/posts/create'))
        ->toBe('/posts/create');
    expect(ListHeaderActions::sanitizeHref('posts/create'))
        ->toBe('/posts/create');
});

test('sanitizeHref rejects unsafe or external URLs by default', function () {
    expect(ListHeaderActions::sanitizeHref('javascript:alert(1)'))->toBe('');
    expect(ListHeaderActions::sanitizeHref('data:text/html;base64,abc'))->toBe('');
    expect(ListHeaderActions::sanitizeHref('//cdn.example/x'))->toBe('');
    expect(ListHeaderActions::sanitizeHref('https://evil.example/p'))->toBe('');
});

test('sanitizeHref allows external URLs when explicitly enabled', function () {
    expect(ListHeaderActions::sanitizeHref('https://evil.example/p', true))
        ->toBe('https://evil.example/p');
});
