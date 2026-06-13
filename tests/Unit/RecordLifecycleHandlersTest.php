<?php

declare(strict_types=1);

use Flatpack\Actions\ActionContext;
use Flatpack\Actions\Handlers\DeleteRecordHandler;
use Flatpack\Actions\Handlers\EditRecordHandler;
use Flatpack\Actions\Handlers\ForceDeleteRecordHandler;
use Flatpack\Actions\Handlers\RestoreRecordHandler;
use Flatpack\Tests\Models\Category;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

uses(TestCase::class, RefreshDatabase::class);

test('DeleteRecordHandler throws when model is not a persisted Eloquent row', function () {
    $context = new ActionContext(
        request: Request::create('/flatpack/posts/1', 'DELETE'),
        entity: 'posts',
        actionName: 'delete',
        modelClass: Post::class,
        record: '1',
        compositionType: 'form',
        schema: null,
        model: new stdClass,
    );

    expect(fn () => app(DeleteRecordHandler::class)->handle($context))
        ->toThrow(ModelNotFoundException::class);
});

test('DeleteRecordHandler deletes model and returns redirect', function () {
    $post = Post::factory()->create();

    $context = new ActionContext(
        request: Request::create('/flatpack/posts/' . $post->getKey(), 'DELETE'),
        entity: 'posts',
        actionName: 'delete',
        modelClass: Post::class,
        record: (string) $post->getKey(),
        compositionType: 'form',
        schema: null,
        model: $post,
    );

    $result = app(DeleteRecordHandler::class)->handle($context);

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and(Post::withTrashed()->find($post->getKey()))->not->toBeNull()
        ->and(Post::withTrashed()->find($post->getKey())?->trashed())->toBeTrue();
});

test('EditRecordHandler redirects to edit route when model exists', function () {
    $post = Post::factory()->create();

    $context = new ActionContext(
        request: Request::create('/flatpack/posts/' . $post->getKey() . '/edit', 'GET'),
        entity: 'posts',
        actionName: 'edit',
        modelClass: Post::class,
        record: (string) $post->getKey(),
        compositionType: 'form',
        schema: null,
        model: $post,
    );

    $result = app(EditRecordHandler::class)->handle($context);

    expect($result)->toBeInstanceOf(RedirectResponse::class);
});

test('EditRecordHandler throws when model is missing', function () {
    $context = new ActionContext(
        request: Request::create('/flatpack/posts/1/edit', 'GET'),
        entity: 'posts',
        actionName: 'edit',
        modelClass: Post::class,
        record: '1',
        compositionType: 'form',
        schema: null,
        model: null,
    );

    expect(fn () => app(EditRecordHandler::class)->handle($context))
        ->toThrow(ModelNotFoundException::class);
});

test('ForceDeleteRecordHandler force deletes model and returns redirect', function () {
    $post = Post::factory()->create();

    $context = new ActionContext(
        request: Request::create('/flatpack/posts/' . $post->getKey() . '/force', 'DELETE'),
        entity: 'posts',
        actionName: 'forceDelete',
        modelClass: Post::class,
        record: (string) $post->getKey(),
        compositionType: 'form',
        schema: null,
        model: $post,
    );

    $result = app(ForceDeleteRecordHandler::class)->handle($context);

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and(Post::query()->find($post->getKey()))->toBeNull();
});

test('ForceDeleteRecordHandler throws when model is not a persisted Eloquent row', function () {
    $context = new ActionContext(
        request: Request::create('/flatpack/posts/1/force', 'DELETE'),
        entity: 'posts',
        actionName: 'forceDelete',
        modelClass: Post::class,
        record: '1',
        compositionType: 'form',
        schema: null,
        model: new stdClass,
    );

    expect(fn () => app(ForceDeleteRecordHandler::class)->handle($context))
        ->toThrow(ModelNotFoundException::class);
});

test('RestoreRecordHandler throws when model does not support restore', function () {
    $category = Category::factory()->create();

    $context = new ActionContext(
        request: Request::create('/flatpack/categories/' . $category->getKey() . '/restore', 'POST'),
        entity: 'categories',
        actionName: 'restore',
        modelClass: Category::class,
        record: (string) $category->getKey(),
        compositionType: 'form',
        schema: null,
        model: $category,
    );

    expect(fn () => app(RestoreRecordHandler::class)->handle($context))
        ->toThrow(InvalidArgumentException::class);
});

test('RestoreRecordHandler restores soft-deleted model and returns redirect', function () {
    $post = Post::factory()->create();
    $post->delete();

    $context = new ActionContext(
        request: Request::create('/flatpack/posts/' . $post->getKey() . '/restore', 'POST'),
        entity: 'posts',
        actionName: 'restore',
        modelClass: Post::class,
        record: (string) $post->getKey(),
        compositionType: 'form',
        schema: null,
        model: $post->fresh(),
    );

    $result = app(RestoreRecordHandler::class)->handle($context);

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and(Post::withTrashed()->find($post->getKey())?->trashed())->toBeFalse();
});
