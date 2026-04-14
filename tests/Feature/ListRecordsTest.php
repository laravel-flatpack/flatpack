<?php

declare(strict_types=1);

use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\actingAs;

uses(TestCase::class, RefreshDatabase::class);

test('flatpack entity list JSON includes rows from the configured model', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-data-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  id:
    label: ID
  title:
    label: Title
YAML);
        config()->set('flatpack.path', $tempPath);

        Post::factory()->create(['title' => 'Listed post']);

        /** @var User $user */
        $user = User::factory()->createOne();

        $payload = actingAs($user)
            ->getJson(route('flatpack.entities.index', ['entity' => 'posts', 'json' => true]))
            ->assertOk()
            ->json();

        $body = $payload['data'] ?? $payload;
        expect($body['records'])->toBeArray();
        expect($body['records'][0]['title'])->toBe('Listed post');
        expect($body['pagination']['total'])->toBe(1);
        expect($body['pagination']['current_page'])->toBe(1);
        expect($body['model_key'])->toBe('id');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity list JSON exposes configured model primary key name', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-model-key-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\PostBySlug
columns:
  title:
    label: Title
YAML);
        config()->set('flatpack.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();

        $payload = actingAs($user)
            ->getJson(route('flatpack.entities.index', ['entity' => 'posts', 'json' => true]))
            ->assertOk()
            ->json();

        $body = $payload['data'] ?? $payload;
        expect($body['model_key'])->toBe('slug');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity list JSON search filters across all paginated records', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-search-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  id:
    label: ID
  title:
    label: Title
    searchable: true
YAML);
        config()->set('flatpack.path', $tempPath);

        Post::factory()->create(['title' => 'Alpha post']);
        Post::factory()->create(['title' => 'Beta target']);
        Post::factory()->create(['title' => 'Gamma post']);

        /** @var User $user */
        $user = User::factory()->createOne();

        $payload = actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'per_page' => 1,
                'search' => 'target',
            ]))
            ->assertOk()
            ->json();

        $body = $payload['data'] ?? $payload;
        expect($body['records'])->toHaveCount(1);
        expect($body['records'][0]['title'])->toBe('Beta target');
        expect($body['pagination']['total'])->toBe(1);
        expect($body['search_term'])->toBe('target');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity list JSON applies select filters', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-select-filter-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  id:
    label: ID
  title:
    label: Title
  status:
    label: Status
    type: select
    options:
      draft: Draft
      active: Active
      inactive: Inactive
filters:
  status:
    label: Filter by status
    placeholder: Select status
    type: select
YAML);
        config()->set('flatpack.path', $tempPath);

        Post::factory()->create(['title' => 'Draft post', 'status' => 'draft']);
        Post::factory()->create(['title' => 'Active post', 'status' => 'active']);

        /** @var User $user */
        $user = User::factory()->createOne();

        $payload = actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'filters' => ['status' => 'draft'],
            ]))
            ->assertOk()
            ->json();

        $body = $payload['data'] ?? $payload;
        expect($body['records'])->toHaveCount(1);
        expect($body['records'][0]['title'])->toBe('Draft post');
        expect($body['filter_values']['status'])->toBe('draft');
        expect($body['filters'][0]['type'])->toBe('select');
        expect($body['filters'][0]['label'])->toBe('Filter by status');
        expect($body['filters'][0]['placeholder'])->toBe('Select status');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity list JSON applies multi select filters', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-multi-select-filter-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  id:
    label: ID
  title:
    label: Title
  status:
    label: Status
    type: select
    options:
      draft: Draft
      active: Active
      inactive: Inactive
filters:
  status:
    multiple: true
YAML);
        config()->set('flatpack.path', $tempPath);

        Post::factory()->create(['title' => 'Draft post', 'status' => 'draft']);
        Post::factory()->create(['title' => 'Active post', 'status' => 'active']);
        Post::factory()->create(['title' => 'Inactive post', 'status' => 'inactive']);

        /** @var User $user */
        $user = User::factory()->createOne();

        $payload = actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'filters' => ['status' => ['draft', 'inactive']],
            ]))
            ->assertOk()
            ->json();

        $body = $payload['data'] ?? $payload;
        expect($body['records'])->toHaveCount(2);
        expect(collect($body['records'])->pluck('status')->all())
            ->toContain('draft')
            ->toContain('inactive');
        expect($body['filter_values']['status'])
            ->toContain('draft')
            ->toContain('inactive');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity list JSON applies date exact and from filters', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-date-filter-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  id:
    label: ID
  title:
    label: Title
  created_at:
    label: Created At
    type: date
filters:
  created_at:
    label: Filter by published at
    placeholder: Select a date
    type: date
    mode: from
YAML);
        config()->set('flatpack.path', $tempPath);

        $older = Post::factory()->create(['title' => 'Older post']);
        $older->created_at = '2024-01-10 08:00:00';
        $older->save();

        $newer = Post::factory()->create(['title' => 'Newer post']);
        $newer->created_at = '2024-01-20 08:00:00';
        $newer->save();

        /** @var User $user */
        $user = User::factory()->createOne();

        $fromPayload = actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'filters' => ['created_at' => '2024-01-15'],
            ]))
            ->assertOk()
            ->json();
        $fromBody = $fromPayload['data'] ?? $fromPayload;
        expect($fromBody['records'])->toHaveCount(1);
        expect($fromBody['records'][0]['title'])->toBe('Newer post');
        expect($fromBody['filters'][0]['label'])->toBe('Filter by published at');
        expect($fromBody['filters'][0]['placeholder'])->toBe('Select a date');

        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  id:
    label: ID
  title:
    label: Title
  created_at:
    label: Created At
    type: date
filters:
  created_at:
    mode: exact
YAML);

        $exactPayload = actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'filters' => ['created_at' => '2024-01-10'],
            ]))
            ->assertOk()
            ->json();
        $exactBody = $exactPayload['data'] ?? $exactPayload;
        expect($exactBody['records'])->toHaveCount(1);
        expect($exactBody['records'][0]['title'])->toBe('Older post');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity list JSON applies sortable column ordering', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-sorting-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  id:
    label: ID
  title:
    label: Title
  created_at:
    label: Created At
    type: date
    sortable: true
YAML);
        config()->set('flatpack.path', $tempPath);

        $older = Post::factory()->create(['title' => 'Older post']);
        $older->created_at = '2024-01-10 08:00:00';
        $older->save();

        $newer = Post::factory()->create(['title' => 'Newer post']);
        $newer->created_at = '2024-01-20 08:00:00';
        $newer->save();

        /** @var User $user */
        $user = User::factory()->createOne();

        $payload = actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'sort_by' => 'created_at',
                'sort_direction' => 'asc',
            ]))
            ->assertOk()
            ->json();

        $body = $payload['data'] ?? $payload;
        expect($body['records'])->toHaveCount(2);
        expect($body['records'][0]['title'])->toBe('Older post');
        expect($body['records'][1]['title'])->toBe('Newer post');
        expect($body['sorting'])->toMatchArray([
            'sort_by' => 'created_at',
            'sort_direction' => 'asc',
        ]);
    } finally {
        File::deleteDirectory($tempPath);
    }
});
