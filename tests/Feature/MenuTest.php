<?php

declare(strict_types=1);

use Flatpack\Tests\Models\User;
use Flatpack\Tests\Policies\DenyViewPostPolicy;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;

uses(TestCase::class, RefreshDatabase::class);

test('flatpack shares menu from config override', function () {
    config()->set('flatpack.ui.navigation.menu', [
        'posts' => [
            'name' => 'Posts',
            'url' => 'flatpack.posts.index',
            'icon' => 'book-open',
        ],
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('flatpack.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('flatpack.menu.0.slug', 'posts')
            ->where('flatpack.menu.0.name', 'Posts')
            ->where('flatpack.menu.0.url', 'flatpack.posts.index')
            ->where('flatpack.menu.0.icon', 'book-open')
        );
});

test('flatpack builds default menu from filesystem path when menu override is null', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-menu-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::ensureDirectoryExists($tempPath . '/categories');
        config()->set('flatpack.composition.path', $tempPath);
        config()->set('flatpack.ui.navigation.menu', null);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('flatpack.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('flatpack.menu', 2)
                ->where('flatpack.menu.0.slug', 'categories')
                ->where('flatpack.menu.0.name', 'Categories')
                ->where('flatpack.menu.0.url', url('/flatpack/categories'))
                ->where('flatpack.menu.1.slug', 'posts')
                ->where('flatpack.menu.1.name', 'Posts')
                ->where('flatpack.menu.1.url', url('/flatpack/posts'))
            );
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack supports an explicit empty menu override', function () {
    config()->set('flatpack.ui.navigation.menu', []);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('flatpack.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('flatpack.menu', [])
        );
});

test('flatpack filters unsafe menu routes from config override', function () {
    config()->set('app.url', 'https://app.test');
    config()->set('flatpack.ui.navigation.menu', [
        'safe-relative' => [
            'name' => 'Safe Relative',
            'url' => '/flatpack/posts',
            'icon' => 'book-open',
        ],
        'safe-same-origin' => [
            'name' => 'Safe Same Origin',
            'url' => 'https://app.test/flatpack/categories',
            'icon' => 'folder',
        ],
        'unsafe-javascript' => [
            'name' => 'Unsafe JS',
            'url' => 'javascript:alert(1)',
            'icon' => 'bug',
        ],
        'unsafe-external' => [
            'name' => 'Unsafe External',
            'url' => 'https://evil.example/phish',
            'icon' => 'triangle-alert',
        ],
        'unsafe-protocol-relative' => [
            'name' => 'Unsafe Protocol Relative',
            'url' => '//cdn.example/path',
            'icon' => 'link',
        ],
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('flatpack.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('flatpack.menu', 2)
            ->where('flatpack.menu.0.slug', 'safe-relative')
            ->where('flatpack.menu.0.url', '/flatpack/posts')
            ->where('flatpack.menu.1.slug', 'safe-same-origin')
            ->where('flatpack.menu.1.url', 'https://app.test/flatpack/categories')
        );
});

test('flatpack hides filesystem menu entries when policy denies viewAny or viewAll', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-menu-policy-filter-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::ensureDirectoryExists($tempPath . '/categories');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
YAML);
        File::put($tempPath . '/categories/list.yaml', <<<'YAML'
name: Categories
model: Flatpack\Tests\Models\PostBySlug
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        config()->set('flatpack.ui.navigation.menu', null);
        Gate::policy(Flatpack\Tests\Models\Post::class, DenyViewPostPolicy::class);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('flatpack.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('flatpack.menu', 1)
                ->where('flatpack.menu.0.slug', 'categories')
            );
    } finally {
        File::deleteDirectory($tempPath);
    }
});
