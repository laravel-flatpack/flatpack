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
    $response = $this->call('GET', '/backend/posts');

    $this->assertEquals(302, $response->status());
    $this->assertTrue(
        $response->headers->all('location') === ['http://localhost/backend/login']
    );
});

it('returns error for non existing entity', function () {
    $response = actingAs($this->user)->get('/backend/foo');
    $this->assertEquals(500, $response->status());

    $response = actingAs($this->user)->get('/backend/categories');
    $this->assertEquals(500, $response->status());
});

it('shows the list page', function () {
    $response = actingAs($this->user)->get("/backend/posts");
    $response->assertViewIs('flatpack::list');
    $response->assertSuccessful();
    $response->assertViewHasAll([
        'composition' => [
            'columns' => [
                'id' => [
                    'label' => 'ID',
                    'sortable' => true,
                ],
                'title' => [
                    'label' => 'Title',
                    'sortable' => true,
                    'searchable' => true,
                ],
                'created_at' => [
                    'label' => 'Created',
                    'type' => 'datetime',
                    'format' => "M d, Y  h:i a",
                    'sortable' => true,
                ],
                'updated_at' => [
                    'label' => 'Updated',
                    'type' => 'datetime',
                    'format' => "M d, Y  h:i a",
                    'sortable' => true,
                ],
            ],
        ],
        'model' => Post::class,
        'entity' => 'posts',
    ]);
});
