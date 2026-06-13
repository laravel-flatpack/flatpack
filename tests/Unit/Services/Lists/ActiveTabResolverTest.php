<?php

declare(strict_types=1);

use Flatpack\Services\Lists\ActiveTabResolver;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

test('resolve returns null when schema has no tabs', function (): void {
    $resolver = app(ActiveTabResolver::class);

    expect($resolver->resolve(['columns' => ['id' => ['label' => 'ID']]], ''))->toBeNull();
});

test('resolve returns null when schema is null', function (): void {
    $resolver = app(ActiveTabResolver::class);

    expect($resolver->resolve(null, ''))->toBeNull();
});

test('resolve picks requested tab when it exists', function (): void {
    $resolver = app(ActiveTabResolver::class);
    $schema = [
        'tabs' => [
            'a' => ['label' => 'Tab A'],
            'b' => ['label' => 'Tab B', 'scope' => 'published'],
        ],
    ];

    $tab = $resolver->resolve($schema, 'b');

    expect($tab)->not->toBeNull()
        ->and($tab['id'])->toBe('b')
        ->and($tab['scope'] ?? null)->toBe('published');
});

test('resolve falls back to first tab when requested id is missing', function (): void {
    $resolver = app(ActiveTabResolver::class);
    $schema = [
        'tabs' => [
            'first' => ['label' => 'First'],
            'second' => ['label' => 'Second'],
        ],
    ];

    $tab = $resolver->resolve($schema, 'unknown');

    expect($tab)->not->toBeNull()
        ->and($tab['id'])->toBe('first');
});

test('schemaForTab returns schema unchanged when active tab is null', function (): void {
    $resolver = app(ActiveTabResolver::class);
    $schema = ['columns' => ['id' => ['label' => 'ID']]];

    expect($resolver->schemaForTab($schema, null))->toBe($schema);
});

test('schemaForTab merges tab overrides onto base schema', function (): void {
    $resolver = app(ActiveTabResolver::class);
    $schema = [
        'columns' => ['id' => ['label' => 'ID']],
        'filters' => ['status' => ['type' => 'select']],
    ];
    $activeTab = [
        'id' => 'scoped',
        'scope' => 'draft',
        'columns' => ['title' => ['label' => 'Title']],
    ];

    $out = $resolver->schemaForTab($schema, $activeTab);

    expect($out['columns'])->toHaveKey('title')
        ->and($out['filters'])->toBe([]);
});

test('resolveWithSchema returns active tab effective schema and scope', function (): void {
    $resolver = app(ActiveTabResolver::class);
    $schema = [
        'columns' => ['id' => ['label' => 'ID']],
        'tabs' => [
            'records' => ['label' => 'Records'],
            'drafts' => [
                'label' => 'Drafts',
                'scope' => 'draftOnly',
                'columns' => ['title' => ['label' => 'Title']],
            ],
        ],
    ];

    $resolved = $resolver->resolveWithSchema($schema, 'drafts');

    expect($resolved->activeTab)->not->toBeNull()
        ->and($resolved->activeTab['id'])->toBe('drafts')
        ->and($resolved->scope)->toBe('draftOnly')
        ->and($resolved->effectiveSchema)->not->toBeNull();

    $effectiveSchema = $resolved->effectiveSchema?->toArray() ?? [];
    expect($effectiveSchema['columns'])->toBeArray()
        ->and($effectiveSchema['columns'][0]['id'] ?? null)->toBe('title');
});
