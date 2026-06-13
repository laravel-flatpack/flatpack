<?php

declare(strict_types=1);

use Flatpack\Schema\Widgets\Normalization\WidgetSchemaNormalizationSupport;
use Flatpack\Schema\Widgets\Normalization\WidgetTypes\TableLikeWidgetNormalizer;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;

test('table widget normalizes model-backed definition', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
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
        ->and($out['widgets']['posts_table']['showColumnsVisibility'])->toBeFalse()
        ->and($out['widgets']['posts_table']['default_sort'])->toBe([
            'key' => 'title',
            'direction' => 'asc',
        ]);
});

test('table widget accepts showColumnsVisibility override for provider-backed widget without yaml columns', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'posts_table' => [
                'type' => 'table',
                'provider' => 'posts_table_provider',
                'showColumnsVisibility' => true,
            ],
        ],
    ]);

    expect($out['widgets']['posts_table']['showColumnsVisibility'])->toBeTrue()
        ->and($out['widgets']['posts_table'])->not->toHaveKey('columns');
});

test('table widget keeps pagination per_page config', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'posts_table' => [
                'type' => 'table',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'columns' => [
                    'title' => [
                        'label' => 'Title',
                    ],
                ],
                'pagination' => [
                    'per_page' => 3,
                ],
            ],
        ],
    ]);

    expect($out['widgets']['posts_table']['pagination'])->toBe([
        'per_page' => 3,
    ]);
});

test('table widget keeps model-backed paginate integer', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'posts_table' => [
                'type' => 'table',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'columns' => [
                    'title' => [
                        'label' => 'Title',
                    ],
                ],
                'paginate' => 9,
            ],
        ],
    ]);

    expect($out['widgets']['posts_table']['paginate'])->toBe(9);
});

test('table widget copies list_entity and actions for model-backed widgets', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'recent' => [
                'type' => 'table',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'list_entity' => 'posts',
                'columns' => [
                    'title' => [
                        'label' => 'Title',
                    ],
                ],
                'actions' => [
                    'create' => [
                        'label' => 'Create Post',
                        'action' => 'create',
                        'variant' => 'primary',
                        'icon' => 'plus',
                    ],
                ],
            ],
        ],
    ]);

    expect($out['widgets']['recent']['list_entity'])->toBe('posts')
        ->and($out['widgets']['recent']['actions']['create']['label'])->toBe('Create Post');
});

test('table widget maps entity alias to list_entity', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'recent' => [
                'type' => 'table',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'entity' => 'posts',
                'columns' => [
                    'title' => [
                        'label' => 'Title',
                    ],
                ],
            ],
        ],
    ]);

    expect($out['widgets']['recent']['list_entity'])->toBe('posts');
});

test('table widget normalizes bulk_actions for model-backed widgets', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
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

test('table widget adds relation edit_form_field for model-backed widgets', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'comments_table' => [
                'type' => 'table',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'columns' => [
                    'user_id' => [
                        'label' => 'User',
                        'type' => 'relation',
                        'relation' => 'user',
                        'relation_name' => 'name',
                        'relation_value' => 'id',
                    ],
                ],
            ],
        ],
    ]);

    expect($out['widgets']['comments_table']['columns']['user_id']['edit_form_field'])->toBe([
        'type' => 'combobox',
        'label' => 'User',
        'placeholder' => 'Select the user',
        'required' => false,
    ]);
});

test('table widget relation edit_form_field allows explicit overrides', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'comments_table' => [
                'type' => 'table',
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'columns' => [
                    'user_id' => [
                        'label' => 'User',
                        'type' => 'relation',
                        'relation' => 'user',
                        'relation_name' => 'name',
                        'relation_value' => 'id',
                        'edit_form_field' => [
                            'placeholder' => 'Pick a user',
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ],
    ]);

    expect($out['widgets']['comments_table']['columns']['user_id']['edit_form_field'])->toBe([
        'type' => 'combobox',
        'label' => 'User',
        'placeholder' => 'Pick a user',
        'required' => true,
    ]);
});

test('table widget normalizes provider-backed definition without yaml columns', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'posts_table' => [
                'type' => 'table',
                'provider' => 'posts_table_provider',
                'label' => 'Posts',
            ],
        ],
    ]);

    expect($out['widgets']['posts_table']['provider'])->toBe('posts_table_provider')
        ->and($out['widgets']['posts_table'])->not->toHaveKey('model')
        ->and($out['widgets']['posts_table'])->not->toHaveKey('columns');
});

test('table widget is skipped when both model and provider are set', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
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

test('table widget is skipped when model-backed columns are missing', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
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

test('table widget accepts yaml columns on provider-backed widget and forces editable false', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'posts_table' => [
                'type' => 'table',
                'provider' => 'posts_table_provider',
                'label' => 'Posts',
                'columns' => [
                    'title' => [
                        'label' => 'Title',
                        'sortable' => true,
                    ],
                    'status' => [
                        'label' => 'Status',
                        'type' => 'badge',
                    ],
                ],
            ],
        ],
    ]);

    expect($out['widgets']['posts_table']['provider'])->toBe('posts_table_provider')
        ->and($out['widgets']['posts_table']['columns'])->toHaveKeys(['title', 'status'])
        ->and($out['widgets']['posts_table']['columns']['title']['editable'])->toBeFalse()
        ->and($out['widgets']['posts_table']['columns']['title']['sortable'])->toBeTrue()
        ->and($out['widgets']['posts_table']['columns']['status']['editable'])->toBeFalse()
        ->and($out['widgets']['posts_table']['columns']['status']['type'])->toBe('badge');
});

test('table widget preserves yaml badge options on provider-backed widget', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'posts_table' => [
                'type' => 'table',
                'provider' => 'posts_table_provider',
                'label' => 'Posts',
                'columns' => [
                    'status' => [
                        'label' => 'Status',
                        'type' => 'badge',
                        'options' => [
                            'draft' => [
                                'value' => 'draft',
                                'label' => 'Draft',
                                'status' => 'pending',
                            ],
                            'published' => [
                                'value' => 'published',
                                'label' => 'Published',
                                'status' => 'success',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    expect($out['widgets']['posts_table']['columns']['status']['options'])->toBe([
        'draft' => [
            'value' => 'draft',
            'label' => 'Draft',
            'status' => 'pending',
        ],
        'published' => [
            'value' => 'published',
            'label' => 'Published',
            'status' => 'success',
        ],
    ]);
});

test('table widget is skipped when model is not a valid eloquent class', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'posts_table' => [
                'type' => 'table',
                'model' => 'DateTime',
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

test('table widget allows missing label without fallback', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport(new TableLikeWidgetNormalizer()));
    $out = $normalizer->normalize([
        'widgets' => [
            'recent_posts' => [
                'type' => 'table',
                'provider' => 'recent_posts',
            ],
        ],
    ]);

    expect($out['widgets']['recent_posts']['label'])->toBeNull();
});
