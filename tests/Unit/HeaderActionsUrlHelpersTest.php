<?php

declare(strict_types=1);

use Flatpack\Schema\HeaderActions;
use Flatpack\Support\CompositionDebugLog;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

test('prefixedUrl joins flatpack prefix and path', function () {
    expect(HeaderActions::prefixedUrl('/posts/create', 'flatpack'))
        ->toBe('/flatpack/posts/create');
    expect(HeaderActions::prefixedUrl('posts/create', 'admin'))
        ->toBe('/admin/posts/create');
});

test('prefixedUrl returns path only when prefix is empty', function () {
    expect(HeaderActions::prefixedUrl('/x', ''))->toBe('/x');
});

test('prefixedUrl leaves absolute URLs unchanged', function () {
    expect(HeaderActions::prefixedUrl('https://example.com/p', 'flatpack', true))
        ->toBe('https://example.com/p');
});

test('prefixedUrl rejects unsafe or external URLs by default', function () {
    expect(HeaderActions::prefixedUrl('javascript:alert(1)', 'flatpack'))->toBe('');
    expect(HeaderActions::prefixedUrl('data:text/html;base64,abc', 'flatpack'))->toBe('');
    expect(HeaderActions::prefixedUrl('//cdn.example/x', 'flatpack'))->toBe('');
    expect(HeaderActions::prefixedUrl('https://evil.example/p', 'flatpack'))->toBe('');
});

test('prefixedUrl allows external URLs when explicitly enabled', function () {
    expect(HeaderActions::prefixedUrl('https://evil.example/p', 'flatpack', true))
        ->toBe('https://evil.example/p');
});

test('sanitizeHref keeps relative paths without prefixing', function () {
    expect(HeaderActions::sanitizeHref('/posts/create'))
        ->toBe('/posts/create');
    expect(HeaderActions::sanitizeHref('posts/create'))
        ->toBe('/posts/create');
});

test('sanitizeHref rejects unsafe or external URLs by default', function () {
    expect(HeaderActions::sanitizeHref('javascript:alert(1)'))->toBe('');
    expect(HeaderActions::sanitizeHref('data:text/html;base64,abc'))->toBe('');
    expect(HeaderActions::sanitizeHref('//cdn.example/x'))->toBe('');
    expect(HeaderActions::sanitizeHref('https://evil.example/p'))->toBe('');
});

test('sanitizeHref allows external URLs when explicitly enabled', function () {
    expect(HeaderActions::sanitizeHref('https://evil.example/p', true))
        ->toBe('https://evil.example/p');
});

test('fromSchema omits unconfigured action handlers and logs a debug warning', function () {
    $debug = new CompositionDebugLog('posts/form.yaml');

    $actions = HeaderActions::fromSchema([
        'actions' => [
            'save' => [
                'label' => 'Save',
                'action' => 'save',
            ],
            'publish' => [
                'label' => 'Publish',
                'action' => 'publish',
            ],
        ],
    ], $debug);

    expect($actions)->toHaveCount(1)
        ->and($actions[0]['id'])->toBe('save')
        ->and($debug->all())->toContain(
            '[posts/form.yaml] Action "publish" in actions.publish.action is not configured in flatpack.actions (omitted from UI).'
        );
});
