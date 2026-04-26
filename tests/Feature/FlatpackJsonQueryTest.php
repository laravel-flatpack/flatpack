<?php

declare(strict_types=1);

use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\actingAs;

uses(TestCase::class, RefreshDatabase::class);

test('flatpack dashboard returns minimal JSON resource when json query is true and no dashboard schema', function () {
    /** @var User $user */
    $user = User::factory()->createOne();

    $payload = actingAs($user)
        ->getJson(route('flatpack.dashboard', ['json' => true]))
        ->assertOk()
        ->json();

    expect($payload)->toBe([
        'schema' => null,
        'composition_debug' => [],
    ]);
});

test('flatpack dashboard returns JSON schema when json query is true and dashboard list yaml exists', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-json-dashboard-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/dashboard');
        File::put($tempPath . '/dashboard/list.yaml', <<<'YAML'
name: Overview
widgets: []
YAML);
        config()->set('flatpack.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.dashboard', ['json' => true]))
            ->assertOk()
            ->assertJson([
                'schema' => [
                    'name' => 'Overview',
                    'widgets' => [],
                ],
            ]);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity list returns JSON schema when json query is true', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-json-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
YAML);
        config()->set('flatpack.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.entities.index', ['entity' => 'posts', 'json' => true]))
            ->assertOk()
            ->assertJson([
                'schema' => [
                    'name' => 'Posts',
                    'model' => 'Flatpack\Tests\Models\Post',
                ],
            ]);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity create returns JSON schema when json query is true', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-json-form-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/form.yaml', <<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields: []
YAML);
        config()->set('flatpack.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.entities.create', ['entity' => 'posts', 'json' => true]))
            ->assertOk()
            ->assertJson([
                'schema' => [
                    'name' => 'Post',
                    'fields' => [],
                ],
            ]);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity edit returns JSON schema when json query is true', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-json-edit-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/form.yaml', <<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields: []
YAML);
        config()->set('flatpack.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();

        /** @var Post $post */
        $post = Post::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
                'json' => true,
            ]))
            ->assertOk()
            ->assertJsonPath('schema.name', 'Post');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity edit missing record with empty fields returns form payload', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-json-edit-missing-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/form.yaml', <<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields: []
YAML);
        config()->set('flatpack.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => '999999',
                'json' => true,
            ]))
            ->assertOk()
            ->assertJsonPath('entity', 'posts')
            ->assertJsonPath('schema.name', 'Post')
            ->assertJsonPath('schema.fields', [])
            ->assertJsonPath('composition_debug', []);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack demo returns JSON catalog when json query is true', function () {
    /** @var User $user */
    $user = User::factory()->createOne();

    actingAs($user)
        ->getJson(route('flatpack.demo.components', ['json' => true]))
        ->assertOk()
        ->assertJsonStructure(['catalog'])
        ->assertJsonPath('catalog.0.id', 'text');
});
