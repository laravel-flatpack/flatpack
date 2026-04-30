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
        'widgets' => [],
        'model' => null,
        'model_key' => 'id',
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
        config()->set('flatpack.composition.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.dashboard', ['json' => true]))
            ->assertOk()
            ->assertJson([
                'model' => null,
                'model_key' => 'id',
                'schema' => [
                    'name' => 'Overview',
                    'widgets' => [],
                ],
            ]);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack dashboard forwards widget normalizer debug messages when app debug is enabled', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-json-dashboard-debug-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/dashboard');
        File::put($tempPath . '/dashboard/list.yaml', <<<'YAML'
name: Overview
widgets:
  health_check:
    type: status
    label: Health Check
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        config()->set('app.debug', true);

        /** @var User $user */
        $user = User::factory()->createOne();

        $payload = actingAs($user)
            ->getJson(route('flatpack.dashboard', ['json' => true]))
            ->assertOk()
            ->assertJsonPath('widgets', [])
            ->json();

        expect($payload['composition_debug'][0] ?? '')->toContain(
            'widgets.health_check ignored: status widget requires non-empty provider.',
        );
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
        config()->set('flatpack.composition.path', $tempPath);

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
        config()->set('flatpack.composition.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.entities.create', ['entity' => 'posts', 'json' => true]))
            ->assertOk()
            ->assertJson([
                'model' => 'Flatpack\\Tests\\Models\\Post',
                'model_key' => 'id',
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
        config()->set('flatpack.composition.path', $tempPath);

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
        config()->set('flatpack.composition.path', $tempPath);

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
        ->assertJsonStructure([
            'catalogId',
            'query',
            'document' => [
                'id',
                'title',
                'description',
                'meta',
                'fields',
                'widgets',
            ],
        ])
        ->assertJsonPath('catalogId', 'all')
        ->assertJsonPath('document.id', 'all')
        ->assertJsonPath('document.fields.0.id', 'text');
});

test('flatpack schema form docs return normalized JSON when json query is true', function () {
    /** @var User $user */
    $user = User::factory()->createOne();

    actingAs($user)
        ->getJson(route('flatpack.schema.form', ['json' => true]))
        ->assertOk()
        ->assertJsonPath('schemaType', 'form')
        ->assertJsonPath('document.id', 'form')
        ->assertJsonPath('document.root.key', 'root')
        ->assertJsonStructure([
            'document' => [
                'title',
                'meta' => ['propertyCount', 'definitionCount'],
                'root' => ['properties'],
                'definitions',
            ],
        ]);
});

test('flatpack schema list docs return normalized JSON when json query is true', function () {
    /** @var User $user */
    $user = User::factory()->createOne();

    actingAs($user)
        ->getJson(route('flatpack.schema.list', ['json' => true]))
        ->assertOk()
        ->assertJsonPath('schemaType', 'list')
        ->assertJsonPath('document.id', 'list')
        ->assertJsonPath('document.root.key', 'root')
        ->assertJsonStructure([
            'document' => [
                'title',
                'meta' => ['propertyCount', 'definitionCount'],
                'root' => ['properties'],
                'definitions',
            ],
        ]);
});
