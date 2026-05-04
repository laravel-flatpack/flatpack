<?php

declare(strict_types=1);

use Flatpack\Actions\EntityActionExecutor;
use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Actions\Handlers\FlatpackActionHandler;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Services\Runtime\ActionRuntime;
use Flatpack\Services\SaveRecord\SaveRecordService;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

uses(TestCase::class);

test('FlatpackActionHandler resolveModel returns context model when present', function () {
    $post = new Post;

    $handler = new class(
        app(FlatpackAuthorizer::class),
        app(ActionRuntime::class),
        app(EntityActionExecutor::class),
        app(SaveRecordService::class),
    ) extends FlatpackActionHandler
    {
        public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
        {
            return true;
        }

        public function handle(FlatpackActionContext $context): mixed
        {
            return null;
        }
    };

    $context = new FlatpackActionContext(
        request: Request::create('/x', 'GET'),
        entity: 'posts',
        actionName: 'x',
        modelClass: Post::class,
        record: null,
        compositionType: 'form',
        schema: null,
        model: $post,
    );

    $resolve = new \ReflectionMethod(FlatpackActionHandler::class, 'resolveModel');
    $resolve->setAccessible(true);

    expect($resolve->invoke($handler, $context))->toBe($post);
});

test('FlatpackActionHandler resolveModel instantiates model class when context model is absent', function () {
    $handler = new class(
        app(FlatpackAuthorizer::class),
        app(ActionRuntime::class),
        app(EntityActionExecutor::class),
        app(SaveRecordService::class),
    ) extends FlatpackActionHandler
    {
        public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
        {
            return true;
        }

        public function handle(FlatpackActionContext $context): mixed
        {
            return null;
        }
    };

    $context = new FlatpackActionContext(
        request: Request::create('/x', 'GET'),
        entity: 'posts',
        actionName: 'x',
        modelClass: Post::class,
        record: null,
        compositionType: 'form',
        schema: null,
        model: null,
    );

    $resolve = new \ReflectionMethod(FlatpackActionHandler::class, 'resolveModel');
    $resolve->setAccessible(true);

    expect($resolve->invoke($handler, $context))->toBeInstanceOf(Post::class);
});

test('FlatpackActionHandler modelExists reflects persisted rows', function () {
    $handler = new class(
        app(FlatpackAuthorizer::class),
        app(ActionRuntime::class),
        app(EntityActionExecutor::class),
        app(SaveRecordService::class),
    ) extends FlatpackActionHandler
    {
        public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
        {
            return true;
        }

        public function handle(FlatpackActionContext $context): mixed
        {
            return null;
        }
    };

    $exists = new \ReflectionMethod(FlatpackActionHandler::class, 'modelExists');
    $exists->setAccessible(true);

    $fresh = new Post;
    expect($exists->invoke($handler, null))->toBeFalse()
        ->and($exists->invoke($handler, $fresh))->toBeFalse();

    $persisted = Post::factory()->create();
    expect($exists->invoke($handler, $persisted))->toBeTrue();
});
