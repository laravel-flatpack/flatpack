<?php

declare(strict_types=1);

use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

test('flatpack inertia shares session flash strings for host redirect messages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['success' => 'Post published successfully'])
        ->get(route('flatpack.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('flatpack.flash.success', 'Post published successfully'));
});

test('flatpack inertia omits empty flash keys', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['success' => ''])
        ->get(route('flatpack.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->missing('flatpack.flash.success'));
});
