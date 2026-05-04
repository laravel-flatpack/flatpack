<?php

declare(strict_types=1);

use Flatpack\Schema\Widgets\Normalization\WidgetSchemaNormalizationSupport;
use Flatpack\Schema\Widgets\Normalization\WidgetTypes\TableLikeWidgetNormalizer;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Flatpack\Support\CompositionDebugContext;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

test('grid widget normalizes model-backed definition', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'posts_grid' => [
                'type' => 'grid',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'label' => 'Posts',
                'columns' => [
                    'title' => [
                        'label' => 'Title',
                        'sortable' => true,
                    ],
                ],
                'default_sort' => [
                    'key' => 'title',
                    'direction' => 'asc',
                ],
            ],
        ],
    ]);

    expect($out['widgets']['posts_grid']['type'])->toBe('grid')
        ->and($out['widgets']['posts_grid']['model'])->toBe('Flatpack\\Tests\\Models\\Post')
        ->and($out['widgets']['posts_grid']['columns'])->toHaveKey('title')
        ->and($out['widgets']['posts_grid']['default_sort'])->toBe([
            'key' => 'title',
            'direction' => 'asc',
        ]);
});

test('grid widget normalizes provider-backed definition without yaml columns', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'recent_posts' => [
                'type' => 'grid',
                'provider' => 'recent_posts_provider',
            ],
        ],
    ]);

    expect($out['widgets']['recent_posts']['type'])->toBe('grid')
        ->and($out['widgets']['recent_posts']['provider'])->toBe('recent_posts_provider')
        ->and($out['widgets']['recent_posts'])->not->toHaveKey('columns');
});

test('grid widget rejects definitions with neither or both provider and model', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));

    $neither = $normalizer->normalize([
        'widgets' => [
            'posts_grid' => [
                'type' => 'grid',
                'columns' => ['title' => ['label' => 'Title']],
            ],
        ],
    ]);
    $both = $normalizer->normalize([
        'widgets' => [
            'posts_grid' => [
                'type' => 'grid',
                'provider' => 'posts',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'columns' => ['title' => ['label' => 'Title']],
            ],
        ],
    ]);

    expect($neither['widgets'])->toBeArray()->toBeEmpty()
        ->and($both['widgets'])->toBeArray()->toBeEmpty();
});

test('grid widget keeps bulk_actions, pagination, and list_entity for model-backed widgets', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'recent' => [
                'type' => 'grid',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'list_entity' => 'posts',
                'columns' => ['title' => ['label' => 'Title']],
                'bulk_actions' => [
                    'delete' => [
                        'label' => 'Delete',
                        'action' => 'delete',
                        'variant' => 'destructive',
                    ],
                ],
                'pagination' => ['per_page' => 6],
            ],
        ],
    ]);

    expect($out['widgets']['recent']['list_entity'])->toBe('posts')
        ->and($out['widgets']['recent']['pagination'])->toBe(['per_page' => 6])
        ->and($out['widgets']['recent']['bulk_actions'][0])->toMatchArray([
            'id' => 'delete',
            'label' => 'Delete',
            'action' => 'delete',
            'variant' => 'destructive',
        ]);
});

test('grid widget normalizes optional card slot map and drops unknown column refs', function () {
    config()->set('app.debug', true);
    $debug = app(CompositionDebugContext::class);
    $debug->activate('test');

    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'posts_grid' => [
                'type' => 'grid',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'columns' => [
                    'title' => ['label' => 'Title'],
                    'status' => ['type' => 'badge', 'label' => 'Status'],
                ],
                'card' => [
                    'title' => 'title',
                    'badges' => ['status', 'missing_column'],
                    'body' => ['title'],
                ],
            ],
        ],
    ]);

    expect($out['widgets']['posts_grid']['card'])->toBe([
        'title' => 'title',
        'badges' => ['status'],
        'body' => ['title'],
    ])
        ->and(implode("\n", $debug->lines()))->toContain(
            'card.badges entry ignored: column "missing_column"',
        );
});

test('grid widget omits card key when slot map is empty or absent', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'posts_grid' => [
                'type' => 'grid',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'columns' => ['title' => ['label' => 'Title']],
            ],
            'with_empty_card' => [
                'type' => 'grid',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'columns' => ['title' => ['label' => 'Title']],
                'card' => [],
            ],
        ],
    ]);

    expect($out['widgets']['posts_grid'])->not->toHaveKey('card')
        ->and($out['widgets']['with_empty_card'])->not->toHaveKey('card');
});

test('grid widget rejects an invalid model class', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'broken' => [
                'type' => 'grid',
                'model' => 'App\\Models\\DoesNotExist',
                'columns' => ['title' => ['label' => 'Title']],
            ],
        ],
    ]);

    expect($out['widgets'])->toBeEmpty();
});
