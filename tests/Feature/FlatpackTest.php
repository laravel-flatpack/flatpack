<?php

declare(strict_types=1);

use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;

test('flatpack registers shouldRenderJsonWhen on the exception handler', function () {
    $handler = app(ExceptionHandler::class);
    $reflection = new ReflectionClass(Handler::class);
    $prop = $reflection->getProperty('shouldRenderJsonWhenCallback');
    $prop->setAccessible(true);

    expect($prop->getValue($handler))->not->toBeNull();
});

test('flatpack json rule returns false for unauthenticated flatpack json requests', function () {
    $handler = app(ExceptionHandler::class);
    $reflection = new ReflectionClass(Handler::class);
    $method = $reflection->getMethod('shouldReturnJson');
    $method->setAccessible(true);

    $request = Request::create(route('flatpack.dashboard'), 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    expect($method->invoke($handler, $request, new AuthenticationException))->toBeFalse();
});

test('authenticated users with flatpack access can visit flatpack dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('flatpack.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('flatpack/dashboard', false));
});

test('guests requesting flatpack with Accept application/json are redirected to flatpack login', function () {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->get('/flatpack');

    $response->assertRedirect(route('flatpack.login'));
});
