<?php

declare(strict_types=1);

use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(TestCase::class, RefreshDatabase::class);

test('flatpack shares menu from config override', function () {
    config()->set('flatpack.menu', [
        'posts' => [
            'name' => 'Posts',
            'route' => 'flatpack.posts.index',
            'icon' => 'book-open',
        ],
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('flatpack.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('flatpack.menu.0.slug', 'posts')
            ->where('flatpack.menu.0.name', 'Posts')
            ->where('flatpack.menu.0.route', 'flatpack.posts.index')
            ->where('flatpack.menu.0.icon', 'book-open')
        );
});

test('flatpack builds default menu from filesystem path when menu override is null', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-menu-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::ensureDirectoryExists($tempPath . '/categories');
        config()->set('flatpack.path', $tempPath);
        config()->set('flatpack.menu', null);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('flatpack.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('flatpack.menu', 2)
                ->where('flatpack.menu.0.slug', 'categories')
                ->where('flatpack.menu.0.name', 'Categories')
                ->where('flatpack.menu.0.route', url('/flatpack/categories'))
                ->where('flatpack.menu.1.slug', 'posts')
                ->where('flatpack.menu.1.name', 'Posts')
                ->where('flatpack.menu.1.route', url('/flatpack/posts'))
            );
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack supports an explicit empty menu override', function () {
    config()->set('flatpack.menu', []);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('flatpack.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('flatpack.menu', [])
        );
});

test('flatpack filters unsafe menu routes from config override', function () {
    config()->set('app.url', 'https://app.test');
    config()->set('flatpack.menu', [
        'safe-relative' => [
            'name' => 'Safe Relative',
            'route' => '/flatpack/posts',
            'icon' => 'book-open',
        ],
        'safe-same-origin' => [
            'name' => 'Safe Same Origin',
            'route' => 'https://app.test/flatpack/categories',
            'icon' => 'folder',
        ],
        'unsafe-javascript' => [
            'name' => 'Unsafe JS',
            'route' => 'javascript:alert(1)',
            'icon' => 'bug',
        ],
        'unsafe-external' => [
            'name' => 'Unsafe External',
            'route' => 'https://evil.example/phish',
            'icon' => 'triangle-alert',
        ],
        'unsafe-protocol-relative' => [
            'name' => 'Unsafe Protocol Relative',
            'route' => '//cdn.example/path',
            'icon' => 'link',
        ],
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('flatpack.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('flatpack.menu', 2)
            ->where('flatpack.menu.0.slug', 'safe-relative')
            ->where('flatpack.menu.0.route', '/flatpack/posts')
            ->where('flatpack.menu.1.slug', 'safe-same-origin')
            ->where('flatpack.menu.1.route', 'https://app.test/flatpack/categories')
        );
});
