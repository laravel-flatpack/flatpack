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
    config()->set('flatpack.ui.navigation.main', [
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
        config()->set('flatpack.ui.navigation.main', null);

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

test('flatpack sorts filesystem main menu by list root nav_order not directory order', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-menu-nav-order-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/alpha');
        File::ensureDirectoryExists($tempPath . '/zebra');
        File::put($tempPath . '/alpha/list.yaml', <<<'YAML'
name: Alpha
model: Flatpack\Tests\Models\Post
nav_order: 20
YAML);
        File::put($tempPath . '/zebra/list.yaml', <<<'YAML'
name: Zebra
model: Flatpack\Tests\Models\Post
nav_order: 5
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        config()->set('flatpack.ui.navigation.main', null);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('flatpack.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('flatpack.menu', 2)
                ->where('flatpack.menu.0.slug', 'zebra')
                ->where('flatpack.menu.0.name', 'Zebra')
                ->where('flatpack.menu.1.slug', 'alpha')
                ->where('flatpack.menu.1.name', 'Alpha')
            );
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack sorts secondary filesystem items by list nav_order', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-menu-secondary-nav-order-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/apple');
        File::ensureDirectoryExists($tempPath . '/mule');
        File::ensureDirectoryExists($tempPath . '/solo');
        File::put($tempPath . '/solo/list.yaml', <<<'YAML'
name: Solo
model: Flatpack\Tests\Models\PostBySlug
YAML);
        File::put($tempPath . '/apple/list.yaml', <<<'YAML'
name: Apple
model: Flatpack\Tests\Models\Post
menu: secondary
nav_order: 50
YAML);
        File::put($tempPath . '/mule/list.yaml', <<<'YAML'
name: Mule
model: Flatpack\Tests\Models\Post
menu: secondary
nav_order: 10
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        config()->set('flatpack.ui.navigation.main', null);
        config()->set('flatpack.ui.navigation.secondary', null);
        config()->set('flatpack.ui.navigation.bottom', null);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('flatpack.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('flatpack.menu', 1)
                ->where('flatpack.menu.0.slug', 'solo')
                ->has('flatpack.secondaryMenu.items', 2)
                ->where('flatpack.secondaryMenu.items.0.slug', 'mule')
                ->where('flatpack.secondaryMenu.items.0.name', 'Mule')
                ->where('flatpack.secondaryMenu.items.1.slug', 'apple')
                ->where('flatpack.secondaryMenu.items.1.name', 'Apple')
            );
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack sorts config main override by nav_order', function () {
    config()->set('flatpack.ui.navigation.main', [
        'zebra' => [
            'name' => 'Zebra',
            'url' => '/flatpack/zebra',
            'icon' => 'folder',
            'nav_order' => 20,
        ],
        'alpha' => [
            'name' => 'Alpha',
            'url' => '/flatpack/alpha',
            'icon' => 'folder',
            'nav_order' => 5,
        ],
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('flatpack.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('flatpack.menu', 2)
            ->where('flatpack.menu.0.slug', 'alpha')
            ->where('flatpack.menu.0.name', 'Alpha')
            ->where('flatpack.menu.1.slug', 'zebra')
            ->where('flatpack.menu.1.name', 'Zebra')
        );
});

test('flatpack supports an explicit empty menu override', function () {
    config()->set('flatpack.ui.navigation.main', []);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('flatpack.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('flatpack.menu', [])
        );
});

test('flatpack filters unsafe menu routes from config override', function () {
    config()->set('app.url', 'https://app.test');
    config()->set('flatpack.ui.navigation.main', [
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
        config()->set('flatpack.ui.navigation.main', null);
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

test('flatpack routes list yaml menu option to secondary navigation group', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-menu-placement-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::ensureDirectoryExists($tempPath . '/categories');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
menu: secondary
YAML);
        File::put($tempPath . '/categories/list.yaml', <<<'YAML'
name: Categories
model: Flatpack\Tests\Models\PostBySlug
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        config()->set('flatpack.ui.navigation.main', null);
        config()->set('flatpack.ui.navigation.secondary', null);
        config()->set('flatpack.ui.navigation.bottom', null);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('flatpack.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('flatpack.menu', 1)
                ->where('flatpack.menu.0.slug', 'categories')
                ->where('flatpack.secondaryMenu.items.0.slug', 'posts')
                ->where('flatpack.secondaryMenu.items.0.name', 'Posts')
            );
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack applies secondary group label when items are derived from compositions', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-menu-secondary-label-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::ensureDirectoryExists($tempPath . '/categories');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
menu: secondary
YAML);
        File::put($tempPath . '/categories/list.yaml', <<<'YAML'
name: Categories
model: Flatpack\Tests\Models\PostBySlug
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        config()->set('flatpack.ui.navigation.main', null);
        config()->set('flatpack.ui.navigation.secondary', [
            'label' => 'Workspace',
            'items' => null,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('flatpack.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('flatpack.secondaryMenu.label', 'Workspace')
                ->where('flatpack.secondaryMenu.items.0.slug', 'posts')
            );
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack treats secondary label plus omitted items as derived filesystem links', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-menu-secondary-label-omit-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
menu: secondary
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        config()->set('flatpack.ui.navigation.main', null);
        config()->set('flatpack.ui.navigation.secondary', [
            'label' => 'Tools',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('flatpack.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('flatpack.secondaryMenu.label', 'Tools')
                ->has('flatpack.secondaryMenu.items', 1)
            );
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack resolves slug keyed secondary override without wrapper keys', function () {
    config()->set('flatpack.ui.navigation.main', null);
    config()->set('flatpack.ui.navigation.secondary', [
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
            ->missing('flatpack.secondaryMenu.label')
            ->where('flatpack.secondaryMenu.items.0.slug', 'posts')
            ->where('flatpack.secondaryMenu.items.0.name', 'Posts')
        );
});

test('flatpack uses dashboard route for configured dashboard entity with list metadata', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-menu-dashboard-entity-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/home');
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/home/list.yaml', <<<'YAML'
name: Overview
model: Flatpack\Tests\Models\PostBySlug
icon: house
nav_order: 1
YAML);
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
nav_order: 10
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        config()->set('flatpack.composition.dashboard_entity', 'home');
        config()->set('flatpack.ui.navigation.main', null);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('flatpack.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('flatpack.menu', 2)
                ->where('flatpack.menu.0.slug', 'home')
                ->where('flatpack.menu.0.name', 'Overview')
                ->where('flatpack.menu.0.url', route('flatpack.dashboard'))
                ->where('flatpack.menu.0.icon', 'house')
                ->where('flatpack.menu.1.slug', 'posts')
                ->where('flatpack.menu.1.url', url('/flatpack/posts'))
            );
    } finally {
        File::deleteDirectory($tempPath);
    }
});
