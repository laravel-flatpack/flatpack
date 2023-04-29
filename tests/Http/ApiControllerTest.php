<?php

use Flatpack\Tests\Models\Category;
use Flatpack\Tests\Models\User;

it('should search for suggestions', function () {
    Category::factory()->count(9)->create();
    $category = Category::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('flatpack.api.suggestions', [
            'entity' => 'categories',
            'search' => $category->name,
        ]))
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonFragment([
            'value' => $category->id,
            'display' => $category->name,
        ]);
});

it('should ignore hidden fields', function () {
    User::factory()->count(10)->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('flatpack.api.suggestions', [
            'entity' => 'users',
            'display' => 'password',
        ]))
        ->assertStatus(200)
        ->assertJsonCount(0);
});

it('returns an error for non-existing entity', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('flatpack.api.suggestions', [
            'entity' => 'foo',
        ]))
        ->assertStatus(500);
});
