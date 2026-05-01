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

    expect($payload)->toMatchArray([
        'model' => null,
        'model_key' => 'id',
        'widgets' => [],
        'schema' => ['widgets' => []],
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
                'widgets' => [],
                'schema' => ['widgets' => []],
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

test('flatpack dashboard model-backed table widget json includes pagination metadata', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-json-dashboard-table-pagination-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/dashboard');
        File::put($tempPath . '/dashboard/list.yaml', <<<'YAML'
name: Overview
widgets:
  recent_posts:
    type: table
    model: Flatpack\Tests\Models\Post
    label: Recent Posts
    columns:
      title:
        label: Title
        type: text
        sortable: true
        searchable: true
      created_at:
        label: Created At
        type: date
        sortable: true
    pagination:
      per_page: 2
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();
        Post::factory()->create(['title' => 'One']);
        Post::factory()->create(['title' => 'Two']);
        Post::factory()->create(['title' => 'Three']);

        $payload = actingAs($user)
            ->getJson(route('flatpack.dashboard', ['json' => true]))
            ->assertOk()
            ->json();

        expect($payload['widgets']['recent_posts']['data']['rows'] ?? [])->toHaveCount(2)
            ->and($payload['widgets']['recent_posts']['data']['pagination'] ?? null)->toMatchArray([
                'current_page' => 1,
                'last_page' => 2,
                'per_page' => 2,
                'total' => 3,
                'from' => 1,
                'to' => 2,
            ]);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack dashboard model-backed table widget uses widget-first page param for page 2', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-json-dashboard-table-page2-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/dashboard');
        File::put($tempPath . '/dashboard/list.yaml', <<<'YAML'
name: Overview
widgets:
  recent_posts:
    type: table
    model: Flatpack\Tests\Models\Post
    label: Recent Posts
    columns:
      title:
        label: Title
        type: text
        sortable: true
        searchable: true
    pagination:
      per_page: 2
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();
        Post::factory()->create(['title' => 'One']);
        Post::factory()->create(['title' => 'Two']);
        Post::factory()->create(['title' => 'Three']);

        $payload = actingAs($user)
            ->getJson(route('flatpack.dashboard', [
                'json' => true,
                'recent_posts_page' => 2,
            ]))
            ->assertOk()
            ->json();

        expect($payload['widgets']['recent_posts']['data']['rows'] ?? [])->toHaveCount(1)
            ->and($payload['widgets']['recent_posts']['data']['pagination']['current_page'] ?? null)->toBe(2)
            ->and($payload['widgets']['recent_posts']['data']['pagination']['from'] ?? null)->toBe(3)
            ->and($payload['widgets']['recent_posts']['data']['pagination']['to'] ?? null)->toBe(3);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack dashboard model-backed table widget supports widget-first query params', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-json-dashboard-table-fpw-query-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/dashboard');
        File::put($tempPath . '/dashboard/list.yaml', <<<'YAML'
name: Overview
widgets:
  recent_posts:
    type: table
    model: Flatpack\Tests\Models\Post
    label: Recent Posts
    columns:
      title:
        label: Title
        type: text
        sortable: true
        searchable: true
    pagination:
      per_page: 2
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();
        Post::factory()->create(['title' => 'One']);
        Post::factory()->create(['title' => 'Two']);
        Post::factory()->create(['title' => 'Three']);

        $payload = actingAs($user)
            ->getJson(route('flatpack.dashboard', [
                'json' => true,
                'recent_posts_page' => 2,
                'recent_posts_limit' => 1,
                'recent_posts_sort_by' => 'title',
                'recent_posts_sort_dir' => 'asc',
                'recent_posts_q' => 'Three',
            ]))
            ->assertOk()
            ->json();

        expect($payload['widgets']['recent_posts']['data']['rows'] ?? [])->toHaveCount(0)
            ->and($payload['widgets']['recent_posts']['data']['pagination']['current_page'] ?? null)->toBe(2)
            ->and($payload['widgets']['recent_posts']['data']['pagination']['per_page'] ?? null)->toBe(1)
            ->and($payload['widgets']['recent_posts']['data']['search'] ?? null)->toBe('Three')
            ->and($payload['widgets']['recent_posts']['data']['sorting'] ?? [])->toMatchArray([
                'sort_by' => 'title',
                'sort_direction' => 'asc',
            ]);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack dashboard model-backed table widget defaults per_page to 5 when pagination is omitted', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-json-dashboard-table-default-per-page-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/dashboard');
        File::put($tempPath . '/dashboard/list.yaml', <<<'YAML'
name: Overview
widgets:
  recent_posts:
    type: table
    model: Flatpack\Tests\Models\Post
    columns:
      title:
        label: Title
        type: text
        sortable: true
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();
        Post::factory()->count(6)->create();

        $payload = actingAs($user)
            ->getJson(route('flatpack.dashboard', ['json' => true]))
            ->assertOk()
            ->json();

        expect($payload['widgets']['recent_posts']['data']['rows'] ?? [])->toHaveCount(5)
            ->and($payload['widgets']['recent_posts']['data']['pagination'] ?? null)->toMatchArray([
                'current_page' => 1,
                'last_page' => 2,
                'per_page' => 5,
                'total' => 6,
                'from' => 1,
                'to' => 5,
            ]);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack dashboard model-backed table widget row action runs delete handler', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-dashboard-widget-row-action-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/dashboard');
        File::put($tempPath . '/dashboard/list.yaml', <<<'YAML'
name: Overview
widgets:
  recent_posts:
    type: table
    model: Flatpack\Tests\Models\Post
    columns:
      id:
        label: ID
        type: text
      actions:
        type: actions
        actions:
          - label: Delete
            action: delete
            variant: destructive
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();
        /** @var Post $post */
        $post = Post::factory()->createOne();

        actingAs($user)
            ->post(route('flatpack.dashboard.widgets.row-action', [
                'widget' => 'recent_posts',
                'record' => (string) $post->getKey(),
            ]), [
                'action' => 'delete',
            ])
            ->assertStatus(303);

        expect(Post::query()->find($post->getKey()))->toBeNull();
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
