<?php

use Flatpack\Tests\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'User Name',
        'email' => 'example@demo.com',
    ]);
});

it('renders login screen', function () {
    $response = $this->get('/backend/login');
    $response->assertStatus(200);
});

it('can authenticate using login screen', function () {
    $response = $this->post('/backend/login', [
        'email' => $this->user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/backend');

    $response = $this->post('backend/logout', []);
    $this->assertGuest();
    $response->assertRedirect('/');
});

it('does not login with wrong credentials', function () {
    $this->post('/backend/login', [
        'email' => $this->user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

it('returns an error when trying too many times', function () {
    $this->resetAuth();

    foreach (range(0, 9) as $attempt) {
        $this->postJson(route('flatpack.login.action'), [
            'email' => $this->user->email,
            'password' => "{TestCase::AUTH_PASSWORD}_{$attempt}",
        ]);
    }

    $this->postJson(route('flatpack.login.action'), [
        'email' => $this->user->email,
        'password' => 'wrong-password',
    ])
    ->assertStatus(422)
    ->assertJson(['message' => 'Too many login attempts.']);

    $this->resetAuth();
});
