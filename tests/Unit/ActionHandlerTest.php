<?php

declare(strict_types=1);

use Flatpack\Actions\ActionContext;
use Flatpack\Actions\ActionExecutor;
use Flatpack\Actions\ActionHandler;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Services\Runtime\ActionRuntime;
use Flatpack\Services\SaveRecord\SaveRecordService;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

uses(TestCase::class);

test('ActionHandler resolveModel returns context model when present', function () {
    $post = new Post;

    $handler = new class(app(FlatpackAuthorizer::class), app(ActionRuntime::class), app(ActionExecutor::class), app(SaveRecordService::class)) extends ActionHandler
    {
        public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
        {
            return true;
        }

        public function handle(ActionContext $context): mixed
        {
            return null;
        }
    };

    $context = new ActionContext(
        request: Request::create('/x', 'GET'),
        entity: 'posts',
        actionName: 'x',
        modelClass: Post::class,
        record: null,
        compositionType: 'form',
        schema: null,
        model: $post,
    );

    $resolve = new ReflectionMethod(ActionHandler::class, 'resolveModel');
    $resolve->setAccessible(true);

    expect($resolve->invoke($handler, $context))->toBe($post);
});

test('ActionHandler resolveModel instantiates model class when context model is absent', function () {
    $handler = new class(app(FlatpackAuthorizer::class), app(ActionRuntime::class), app(ActionExecutor::class), app(SaveRecordService::class)) extends ActionHandler
    {
        public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
        {
            return true;
        }

        public function handle(ActionContext $context): mixed
        {
            return null;
        }
    };

    $context = new ActionContext(
        request: Request::create('/x', 'GET'),
        entity: 'posts',
        actionName: 'x',
        modelClass: Post::class,
        record: null,
        compositionType: 'form',
        schema: null,
        model: null,
    );

    $resolve = new ReflectionMethod(ActionHandler::class, 'resolveModel');
    $resolve->setAccessible(true);

    expect($resolve->invoke($handler, $context))->toBeInstanceOf(Post::class);
});

test('ActionHandler modelExists reflects persisted rows', function () {
    $handler = new class(app(FlatpackAuthorizer::class), app(ActionRuntime::class), app(ActionExecutor::class), app(SaveRecordService::class)) extends ActionHandler
    {
        public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
        {
            return true;
        }

        public function handle(ActionContext $context): mixed
        {
            return null;
        }
    };

    $exists = new ReflectionMethod(ActionHandler::class, 'modelExists');
    $exists->setAccessible(true);

    $fresh = new Post;
    expect($exists->invoke($handler, null))->toBeFalse()
        ->and($exists->invoke($handler, $fresh))->toBeFalse();

    $persisted = Post::factory()->create();
    expect($exists->invoke($handler, $persisted))->toBeTrue();
});
