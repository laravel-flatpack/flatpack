<?php

declare(strict_types=1);

namespace Flatpack\Tests\Unit\Http\Requests;

use Flatpack\Http\Requests\Concerns\InteractsWithFlatpackAuthorization;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

uses(TestCase::class);

final class FlatpackAuthDummy
{
    use InteractsWithFlatpackAuthorization;

    /**
     * @return false|never
     */
    public function callDenyFlatpackAuthorization(string $message): bool
    {
        return $this->denyFlatpackAuthorization($message);
    }

    /**
     * @return false|never
     */
    public function callDenyFlatpackGateAuthorization(User $user, string $ability, Post $target): bool
    {
        return $this->denyFlatpackGateAuthorization($user, $ability, $target);
    }
}

it('denyFlatpackAuthorization throws when app.debug is true', function (): void {
    config(['app.debug' => true]);

    expect(fn () => (new FlatpackAuthDummy)->callDenyFlatpackAuthorization('not allowed'))
        ->toThrow(AuthorizationException::class, 'not allowed');
});

it('denyFlatpackAuthorization returns false when app.debug is false', function (): void {
    config(['app.debug' => false]);

    expect((new FlatpackAuthDummy)->callDenyFlatpackAuthorization('not allowed'))->toBeFalse();
});

it('denyFlatpackGateAuthorization uses gate message when app.debug is true', function (): void {
    config(['app.debug' => true]);

    Gate::define('stubDenyWithMessage', fn () => Response::deny('policy says no'));

    $user = User::factory()->make();
    $post = Post::factory()->make();

    expect(fn () => (new FlatpackAuthDummy)->callDenyFlatpackGateAuthorization($user, 'stubDenyWithMessage', $post))
        ->toThrow(AuthorizationException::class, 'policy says no');
});

it('denyFlatpackGateAuthorization uses fallback when gate message is empty', function (): void {
    config(['app.debug' => true]);

    Gate::define('stubSilentDeny', fn () => Response::deny());

    $user = User::factory()->make();
    $post = Post::factory()->make();

    expect(fn () => (new FlatpackAuthDummy)->callDenyFlatpackGateAuthorization($user, 'stubSilentDeny', $post))
        ->toThrow(AuthorizationException::class, 'Gate denied "stubSilentDeny" for Post.');
});

it('denyFlatpackGateAuthorization returns false when app.debug is false', function (): void {
    config(['app.debug' => false]);

    Gate::define('stubSilentDeny2', fn () => Response::deny('ignored'));

    $user = User::factory()->make();
    $post = Post::factory()->make();

    expect((new FlatpackAuthDummy)->callDenyFlatpackGateAuthorization($user, 'stubSilentDeny2', $post))->toBeFalse();
});
