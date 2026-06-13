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

test('allows returns false in testing when no policy is registered and flag uses v2 default', function (): void {
    $user = User::factory()->make();
    $authorizer = new PolicyAwareAuthorizer;

    $result = $authorizer->allows($user, 'viewAny', GuineaPigModel::class);

    expect($result)->toBeFalse();
});

test('allows returns true in local when no policy is registered and flag uses local default', function (): void {
    $this->app['env'] = 'local';
    config(['flatpack.security.authorization.allow_when_policy_missing' => true]);

    $user = User::factory()->make();
    $authorizer = new PolicyAwareAuthorizer;

    $result = $authorizer->allows($user, 'viewAny', GuineaPigModel::class);

    expect($result)->toBeTrue();
});

test('allows returns true when no policy is registered and flag is explicitly true', function (): void {
    config(['flatpack.security.authorization.allow_when_policy_missing' => true]);

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

test('does not log a warning in local when allow_when_policy_missing is true', function (): void {
    $this->app->offsetUnset(Illuminate\Console\OutputStyle::class);
    $this->app['env'] = 'local';
    config(['flatpack.security.authorization.allow_when_policy_missing' => true]);

    $handler = new TestHandler;
    Illuminate\Support\Facades\Log::driver()->getLogger()->pushHandler($handler);

    $user = User::factory()->make();
    $authorizer = new PolicyAwareAuthorizer;
    $authorizer->allows($user, 'viewAny', GuineaPigModel::class);

    expect($handler->hasWarnings())->toBeFalse();
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
