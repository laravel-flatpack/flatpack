<?php

declare(strict_types=1);

use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

test('guest can submit valid credentials and reach the flatpack dashboard', function (): void {
    $user = User::factory()->createOne();

    $response = test()->post(route('flatpack.login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('flatpack.dashboard'));
    expect(auth()->check())->toBeTrue();
});

test('invalid login credentials yield validation errors', function (): void {
    $user = User::factory()->createOne();

    $response = test()->from(route('flatpack.login'))->post(route('flatpack.login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
});

test('authenticated user can log out of flatpack', function (): void {
    $user = User::factory()->createOne();

    $response = actingAs($user)->post(route('flatpack.logout'));

    $response->assertRedirect(route('flatpack.login'));
    expect(auth()->check())->toBeFalse();
});

test('flatpack login page moves only flatpack intended urls into flatpack session slot', function (): void {
    $flatpackUrl = route('flatpack.dashboard');

    $response = test()->withSession(['url.intended' => $flatpackUrl])
        ->get(route('flatpack.login'));

    $response->assertOk();
    expect(session()->get('flatpack.url.intended'))->toBe($flatpackUrl)
        ->and(session()->get('url.intended'))->toBeNull();
});

test('successful login restores flatpack scoped intended url for redirect', function (): void {
    $user = User::factory()->createOne();

    $target = route('flatpack.dashboard');

    $response = test()->withSession(['flatpack.url.intended' => $target])->post(
        route('flatpack.login.store'),
        [
            'email' => $user->email,
            'password' => 'password',
        ],
    );

    $response->assertRedirect($target);
});
