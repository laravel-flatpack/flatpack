<?php

declare(strict_types=1);

use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;

test('table widget normalizes model-backed definition', function () {
    $normalizer = new WidgetSchemaNormalizer();
    $out = $normalizer->normalize([
        'widgets' => [
            'posts_table' => [
                'type' => 'table',
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

    expect($out['widgets']['posts_table']['type'])->toBe('table')
        ->and($out['widgets']['posts_table']['model'])->toBe('Flatpack\\Tests\\Models\\Post')
        ->and($out['widgets']['posts_table']['columns'])->toHaveKey('title')
        ->and($out['widgets']['posts_table']['default_sort'])->toBe([
            'key' => 'title',
            'direction' => 'asc',
        ]);
});

test('table widget normalizes bulk_actions for model-backed widgets', function () {
    $normalizer = new WidgetSchemaNormalizer();
    $out = $normalizer->normalize([
        'widgets' => [
            'comments_table' => [
                'type' => 'table',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'columns' => [
                    'title' => [
                        'label' => 'Title',
                    ],
                ],
                'bulk_actions' => [
                    'delete' => [
                        'label' => 'Delete',
                        'action' => 'delete',
                        'variant' => 'destructive',
                    ],
                ],
            ],
        ],
    ]);

    expect($out['widgets']['comments_table']['bulk_actions'])->toBe([
        [
            'id' => 'delete',
            'label' => 'Delete',
            'action' => 'delete',
            'icon' => '',
            'variant' => 'destructive',
        ],
    ]);
});

test('table widget normalizes provider-backed definition', function () {
    $normalizer = new WidgetSchemaNormalizer();
    $out = $normalizer->normalize([
        'widgets' => [
            'posts_table' => [
                'type' => 'table',
                'provider' => 'posts_table_provider',
                'label' => 'Posts',
                'columns' => [
                    'title' => [
                        'label' => 'Title',
                        'editable' => true,
                    ],
                ],
            ],
        ],
    ]);

    expect($out['widgets']['posts_table']['provider'])->toBe('posts_table_provider')
        ->and($out['widgets']['posts_table'])->not->toHaveKey('model')
        ->and($out['widgets']['posts_table']['columns']['title']['editable'])->toBeFalse();
});

test('table widget is skipped when both model and provider are set', function () {
    $normalizer = new WidgetSchemaNormalizer();
    $out = $normalizer->normalize([
        'widgets' => [
            'posts_table' => [
                'type' => 'table',
                'provider' => 'posts_table_provider',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'label' => 'Posts',
                'columns' => [
                    'title' => [
                        'label' => 'Title',
                    ],
                ],
            ],
        ],
    ]);

    expect($out['widgets'])->toBeEmpty();
});

test('table widget is skipped when columns are missing', function () {
    $normalizer = new WidgetSchemaNormalizer();
    $out = $normalizer->normalize([
        'widgets' => [
            'posts_table' => [
                'type' => 'table',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'label' => 'Posts',
            ],
        ],
    ]);

    expect($out['widgets'])->toBeEmpty();
});

test('table widget allows missing label without fallback', function () {
    $normalizer = new WidgetSchemaNormalizer();
    $out = $normalizer->normalize([
        'widgets' => [
            'recent_posts' => [
                'type' => 'table',
                'provider' => 'recent_posts',
                'columns' => [
                    'title' => [
                        'label' => 'Title',
                    ],
                ],
            ],
        ],
    ]);

    expect($out['widgets']['recent_posts']['label'])->toBeNull();
});
