<?php

declare(strict_types=1);

use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

test('flatpack registers shouldRenderJsonWhen on the exception handler', function () {
    $handler = app(ExceptionHandler::class);
    $reflection = new ReflectionClass(Handler::class);
    $prop = $reflection->getProperty('shouldRenderJsonWhenCallback');

    expect($prop->getValue($handler))->not->toBeNull();
});

test('flatpack json rule returns false for unauthenticated flatpack json requests', function () {
    $handler = app(ExceptionHandler::class);
    $reflection = new ReflectionClass(Handler::class);
    $method = $reflection->getMethod('shouldReturnJson');

    $request = Request::create(route('flatpack.dashboard'), 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    expect($method->invoke($handler, $request, new AuthenticationException))->toBeFalse();
});

test('authenticated users with flatpack access can visit flatpack dashboard', function () {
    $user = User::factory()->createOne();
    assert($user instanceof Authenticatable);

    $response = actingAs($user)->get(route('flatpack.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('dashboard', false));
});

test('guests requesting flatpack with Accept application/json are redirected to flatpack login', function () {
    $response = test()->withHeaders([
        'Accept' => 'application/json',
    ])->get('/flatpack');

    $response->assertRedirect(route('flatpack.login'));
});

test('guests requesting non-flatpack auth routes are redirected to host login route', function () {
    if (! Route::has('login')) {
        Route::middleware('web')->get('/host-login', fn () => 'host login')->name('login');
    }

    Route::middleware(['web', 'auth'])->get('/host-auth-only', fn () => 'ok');

    $response = test()->get('/host-auth-only');

    $response->assertRedirect(route('login'));
});

test('guests can view flatpack login page without redirect loop', function () {
    $response = test()->get(route('flatpack.login'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('login', false));
});
