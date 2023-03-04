<?php

use Flatpack\Tests\Models\User;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'User Name',
        'email' => 'example@demo.com',
        'password' => 'password',
    ]);
});

it('redirects to login unauthenticated user', function () {
    $response = $this->call('GET', '/backend');

    $this->assertEquals(302, $response->status());
    $this->assertTrue(
        $response->headers->all('location') === ['http://localhost/backend/login']
    );
});

it('shows the home page', function () {
    $response = actingAs($this->user)->get('/backend');

    $response->assertViewIs('flatpack::home');
    $response->assertSuccessful();
});
