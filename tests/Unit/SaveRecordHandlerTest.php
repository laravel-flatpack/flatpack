<?php

declare(strict_types=1);

use Flatpack\Actions\ActionContext;
use Flatpack\Actions\Handlers\SaveRecordHandler;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
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

    $result = app(SaveRecordHandler::class)->handle(new ActionContext(
        request: $request,
        entity: 'posts',
        actionName: 'save',
        modelClass: Post::class,
        record: (string) $post->getKey(),
        compositionType: 'form',
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

    $result = app(SaveRecordHandler::class)->handle(new ActionContext(
        request: $request,
        entity: 'posts',
        actionName: 'save',
        modelClass: Post::class,
        record: null,
        compositionType: 'form',
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

test('save record handler returns null when model cannot be resolved', function () {
    $request = Request::create('/flatpack/posts', 'POST', [
        'values' => [
            'title' => 'Ignored',
        ],
    ]);

    $result = app(SaveRecordHandler::class)->handle(new ActionContext(
        request: $request,
        entity: 'posts',
        actionName: 'save',
        modelClass: '',
        record: null,
        compositionType: 'form',
        schema: [
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
        ],
        model: null,
    ));

    expect($result)->toBeNull();
});

test('save record handler returns model unchanged when request has no form payload', function () {
    /** @var Post $post */
    $post = Post::factory()->create([
        'title' => 'Existing title',
    ]);

    $request = Request::create('/flatpack/posts/' . $post->getKey(), 'PATCH', []);

    $result = app(SaveRecordHandler::class)->handle(new ActionContext(
        request: $request,
        entity: 'posts',
        actionName: 'save',
        modelClass: Post::class,
        record: (string) $post->getKey(),
        compositionType: 'form',
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

    expect($result)->toBe($post);
    expect($post->fresh()?->title)->toBe('Existing title');
});
