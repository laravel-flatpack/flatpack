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
    $normalizer->normalizedListSchema([
        'name' => 'Posts',
        'asdasdasd' => 'noise',
        'columns' => [],
    ], $log);

    expect($log->all())->not->toBeEmpty();
    expect(implode(' ', $log->all()))->toContain('asdasdasd');
    expect(implode(' ', $log->all()))->toContain('Unknown top-level list key');
});
