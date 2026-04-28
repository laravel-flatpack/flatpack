<?php

declare(strict_types=1);

use Flatpack\Support\PolicyAwareAuthorizer;
use Flatpack\Tests\Models\GuineaPigModel;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Monolog\Handler\TestHandler;

uses(TestCase::class)->afterEach(function (): void {
    // Restore env so teardown migration commands don't run in production mode.
    $this->app['env'] = 'testing';
});

test('allows returns true for user with panel access when no policy is registered and flag is true', function (): void {
    $user = User::factory()->make();
    $authorizer = new PolicyAwareAuthorizer;

    $result = $authorizer->allows($user, 'viewAny', GuineaPigModel::class);

    expect($result)->toBeTrue();
});

test('allows returns false when no policy is registered and flag is false', function (): void {
    config(['flatpack.security.authorization.allow_when_policy_missing' => false]);

    $user = User::factory()->make();
    $authorizer = new PolicyAwareAuthorizer;

    $result = $authorizer->allows($user, 'viewAny', GuineaPigModel::class);

    expect($result)->toBeFalse();
});

test('logs a warning when allow_when_policy_missing is true in production and no policy is registered', function (): void {
    // Clear any console output mock left by previous artisan test runs, then set env.
    // Without this, ConfirmableTrait::confirmToProceed() fires on teardown and hits
    // the leftover OutputStyle mock with an unexpected call.
    $this->app->offsetUnset(Illuminate\Console\OutputStyle::class);
    $this->app['env'] = 'production';
    config(['flatpack.security.authorization.allow_when_policy_missing' => true]);

    $handler = new TestHandler;
    Illuminate\Support\Facades\Log::driver()->getLogger()->pushHandler($handler);

    $user = User::factory()->make();
    $authorizer = new PolicyAwareAuthorizer;
    $authorizer->allows($user, 'viewAny', GuineaPigModel::class);

    expect($handler->hasWarningThatContains('GuineaPigModel'))->toBeTrue();
});

test('does not log a warning when allow_when_policy_missing is false', function (): void {
    $this->app->offsetUnset(Illuminate\Console\OutputStyle::class);
    $this->app['env'] = 'production';
    config(['flatpack.security.authorization.allow_when_policy_missing' => false]);

    $handler = new TestHandler;
    Illuminate\Support\Facades\Log::driver()->getLogger()->pushHandler($handler);

    $user = User::factory()->make();
    $authorizer = new PolicyAwareAuthorizer;
    $authorizer->allows($user, 'viewAny', GuineaPigModel::class);

    expect($handler->hasWarnings())->toBeFalse();
});

test('delegates to Gate when a policy is registered for the model', function (): void {
    $user = User::factory()->make();
    $post = Post::factory()->make();
    $authorizer = new PolicyAwareAuthorizer;

    $result = $authorizer->allows($user, 'viewAny', Post::class);

    expect($result)->toBeTrue();
});
