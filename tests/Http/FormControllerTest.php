<?php

use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'User Name',
        'email' => 'example@demo.com',
        'password' => 'password',
    ]);
});

it('redirects to login unauthenticated user', function () {
    $response = $this->call('GET', '/backend/posts/123');

    $this->assertEquals(302, $response->status());
    $this->assertTrue(
        $response->headers->all('location') === ['http://localhost/backend/login']
    );
});

it('returns error for non existing records', function () {
    $response = actingAs($this->user)->get('/backend/posts/123');
    $this->assertEquals(404, $response->status());
});

it('shows the create form page', function () {
    $response = actingAs($this->user)->get("/backend/posts/create");
    $response->assertViewIs('flatpack::detail');
    $response->assertSuccessful();
    $response->assertViewHasAll([
        'model' => Post::class,
        'entity' => 'posts',
        'entry' => new Post(),
        'formType' => 'create',
    ]);
});


it('shows the edit form page', function () {
    $post = Post::create([
        'title' => 'Lorem Ipsum',
        'body' => 'Lorem ipsum dolor sit amet',
    ]);
    $response = actingAs($this->user)->get("/backend/posts/{$post->id}");
    $response->assertViewIs('flatpack::detail');
    $response->assertSuccessful();
    $response->assertViewHasAll([
        'model' => Post::class,
        'entity' => 'posts',
        'entry' => $post,
        'formType' => 'edit',
    ]);
});
