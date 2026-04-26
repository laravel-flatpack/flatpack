<?php

declare(strict_types=1);

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Actions\RelationFormSynchronizer;
use Flatpack\Actions\Handlers\SaveRecordHandler;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(TestCase::class, RefreshDatabase::class);

test('save record handler supports writable fields from form schema', function () {
    /** @var Post $post */
    $post = Post::factory()->create([
        'title' => 'Original title',
    ]);

    $request = Request::create('/flatpack/posts/' . $post->getKey(), 'PATCH', [
        'values' => [
            'title' => 'Updated from form schema',
        ],
    ]);

    $result = app(SaveRecordHandler::class)->handle(new FlatpackActionContext(
        request: $request,
        entity: 'posts',
        actionName: 'save',
        modelClass: Post::class,
        record: (string) $post->getKey(),
        compositionType: 'form',
        composition: [
            'model' => Post::class,
            'schema' => [
                'fields' => [
                    'title' => [
                        'type' => 'text',
                        'label' => 'Title',
                    ],
                ],
            ],
        ],
        schema: [
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
        ],
        model: $post,
    ));

    expect($result?->title)->toBe('Updated from form schema');
    expect($post->fresh()?->title)->toBe('Updated from form schema');
});

test('save record handler creates a new model from form schema', function () {
    $request = Request::create('/flatpack/posts', 'POST', [
        'values' => [
            'title' => 'Created from form schema',
            'slug' => 'created-from-form-schema',
            'status' => 'draft',
        ],
    ]);

    $result = app(SaveRecordHandler::class)->handle(new FlatpackActionContext(
        request: $request,
        entity: 'posts',
        actionName: 'save',
        modelClass: Post::class,
        record: null,
        compositionType: 'form',
        composition: [
            'model' => Post::class,
            'schema' => [
                'fields' => [
                    'title' => [
                        'type' => 'text',
                        'label' => 'Title',
                    ],
                    'slug' => [
                        'type' => 'text',
                        'label' => 'Slug',
                    ],
                    'status' => [
                        'type' => 'select',
                        'label' => 'Status',
                    ],
                ],
            ],
        ],
        schema: [
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'label' => 'Title',
                ],
                'slug' => [
                    'type' => 'text',
                    'label' => 'Slug',
                ],
                'status' => [
                    'type' => 'select',
                    'label' => 'Status',
                ],
            ],
        ],
        model: null,
    ));

    expect($result)->toBeInstanceOf(Post::class);
    expect($result?->exists)->toBeTrue();
    expect($result?->title)->toBe('Created from form schema');
    expect($result?->slug)->toBe('created-from-form-schema');
    expect($result?->status)->toBe('draft');
    expect(Post::query()->where('title', 'Created from form schema')->exists())
        ->toBeTrue();
});

test('save record handler maps relation sync required-column DB errors to nested table keys', function () {
    $handler = new SaveRecordHandler(app(RelationFormSynchronizer::class));
    $method = new ReflectionMethod(SaveRecordHandler::class, 'relationSyncValidationError');
    $method->setAccessible(true);

    $schema = [
        'fields' => [
            'comments' => [
                'type' => 'table',
                'label' => 'Comments',
                'relation' => 'comments',
                'columns' => [
                    'content' => [
                        'label' => 'Content',
                        'type' => 'text',
                    ],
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
    ];
    $values = [
        'comments' => [
            [
                'content' => 'Missing user id',
                'user_id' => '',
            ],
        ],
    ];

    $queryException = new QueryException(
        'sqlite',
        'insert into "post_comments" ("user_id") values (?)',
        [],
        new PDOException('NOT NULL constraint failed: post_comments.user_id'),
    );

    $result = $method->invoke($handler, $queryException, $schema, $values);

    expect($result)->toBe([
        'field' => 'values.comments.0.user_id',
        'message' => 'User id is required.',
    ]);
});

test('save record handler validates required table columns for all invalid rows', function () {
    $handler = new SaveRecordHandler(app(RelationFormSynchronizer::class));
    $method = new ReflectionMethod(SaveRecordHandler::class, 'deferredRelationRequiredErrors');
    $method->setAccessible(true);

    $schema = [
        'fields' => [
            'comments' => [
                'type' => 'table',
                'label' => 'Comments',
                'relation' => 'comments',
                'columns' => [
                    'content' => [
                        'label' => 'Content',
                        'type' => 'text',
                    ],
                    'user_id' => [
                        'label' => 'User',
                        'type' => 'relation',
                        'edit_form_field' => [
                            'type' => 'combobox',
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ],
    ];
    $values = [
        'comments' => [
            ['content' => 'A', 'user_id' => ''],
            ['content' => 'B', 'user_id' => null],
        ],
    ];

    $result = $method->invoke($handler, $schema, $values);

    expect($result)->toHaveKey('values.comments.0.user_id');
    expect($result)->toHaveKey('values.comments.1.user_id');
});
