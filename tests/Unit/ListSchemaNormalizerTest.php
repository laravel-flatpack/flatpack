<?php

declare(strict_types=1);

use Flatpack\Schema\Lists\ListSchemaNormalizer;
use Flatpack\Support\CompositionDebugLog;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

it('records unknown keys under list actions in debug log', function (): void {
    $log = new CompositionDebugLog('posts/list.yaml');
    $normalizer = new ListSchemaNormalizer;
    $normalizer->normalizedListSchema([
        'name' => 'Posts',
        'columns' => [],
        'actions' => [
            'create' => [
                'labelllllll' => 'Add',
                'href' => '/add',
            ],
        ],
    ], $log);

    $joined = implode(' ', $log->all());
    expect($joined)->toContain('labelllllll');
    expect($joined)->toContain('actions.create');
});

it('records unknown keys under list column row actions in debug log', function (): void {
    $log = new CompositionDebugLog('posts/list.yaml');
    $normalizer = new ListSchemaNormalizer;
    $normalizer->normalizedListSchema([
        'name' => 'Posts',
        'columns' => [
            [
                'id' => 'actions',
                'type' => 'actions',
                'label' => 'Actions',
                'actions' => [
                    [
                        'label' => 'Delete',
                        'action' => 'delete',
                        'bogus_flag' => true,
                    ],
                ],
            ],
        ],
    ], $log);

    $joined = implode(' ', $log->all());
    expect($joined)->toContain('bogus_flag');
    expect($joined)->toContain('columns.actions.actions.0');
});

it('records unknown top-level list keys in debug log', function (): void {
    $log = new CompositionDebugLog('posts/list.yaml');
    $normalizer = new ListSchemaNormalizer;
    $schema = $normalizer->normalizedListSchema([
        'name' => 'Posts',
        'asdasdasd' => 'noise',
        'columns' => [],
    ], $log);

    expect($schema)->not->toBeNull()
        ->and(isset($schema['asdasdasd']))->toBeFalse();
    expect($log->all())->not->toBeEmpty();
    expect(implode(' ', $log->all()))->toContain('asdasdasd');
    expect(implode(' ', $log->all()))->toContain('Unknown top-level list key');
});

it('does not log normalized tab_panels as unknown root keys', function (): void {
    $log = new CompositionDebugLog('posts/list.yaml');
    $normalizer = new ListSchemaNormalizer;
    $normalizer->normalizedListSchema([
        'name' => 'Posts',
        'columns' => [
            'title' => [
                'label' => 'Title',
                'type' => 'text',
            ],
        ],
        'tabs' => [
            'records' => [
                'label' => 'Records',
            ],
        ],
    ], $log);

    expect(implode(' ', $log->all()))->not->toContain('tab_panels');
});

it('resolves reorderableColumn in normalized list schema', function (): void {
    $normalizer = new ListSchemaNormalizer;

    $schema = $normalizer->normalizedListSchema([
        'name' => 'Posts',
        'reorderable' => 'sorting_order',
        'columns' => [
            'title' => [
                'label' => 'Title',
                'type' => 'text',
            ],
        ],
    ])?->toArray();

    expect($schema)->not->toBeNull()
        ->and($schema)->toHaveKey('reorderableColumn')
        ->and($schema['reorderableColumn'])->toBe('sorting_order');
});

it('coerces invalid list menu to main and logs when debug log is present', function (): void {
    $log = new CompositionDebugLog('posts/list.yaml');
    $normalizer = new ListSchemaNormalizer;

    $schema = $normalizer->normalizedListSchema([
        'name' => 'Posts',
        'menu' => 'sidebar',
        'columns' => [],
    ], $log)?->toArray();

    expect($schema)->not->toBeNull()
        ->and($schema['menu'])->toBe('main')
        ->and(implode(' ', $log->all()))->toContain('Invalid list `menu`');
});
