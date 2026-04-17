<?php

declare(strict_types=1);

use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\Policies\DenyDeletePostPolicy;
use Flatpack\Tests\Policies\DenyUpdatePostPolicy;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;

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

test('flatpack entity list JSON only exposes valid configured header actions', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-header-actions-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
actions:
  create:
    label: Create
    action: create
    variant: primary
    success_message: Created
    confirm: true
    success_redirect: list
  docs:
    label: Docs
    href: /docs/posts
  invalid:
    label: Invalid
    action: create
    href: /posts/create
  legacy:
    label: Legacy
    url: /posts/create
columns:
  id:
    label: ID
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
        expect($body['list_actions'])->toEqual([
            [
                'id' => 'create',
                'label' => 'Create',
                'icon' => '',
                'action' => 'create',
                'variant' => 'default',
                'success_message' => 'Created',
                'confirm' => true,
                'success_redirect' => 'list',
            ],
            [
                'id' => 'docs',
                'label' => 'Docs',
                'icon' => '',
                'href' => '/docs/posts',
                'variant' => 'outline',
            ],
        ]);
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

test('flatpack list action route resolves configured create handler', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-action-route-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  id:
    label: ID
YAML);
        config()->set('flatpack.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->post(route('flatpack.entities.action', [
                'entity' => 'posts',
            ]), [
                'action' => 'create',
            ])
            ->assertStatus(303)
            ->assertRedirect(route('flatpack.entities.create', [
                'entity' => 'posts',
            ]));
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity action route requires configured action handlers', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-action-fallback-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  id:
    label: ID
YAML);
        config()->set('flatpack.path', $tempPath);
        config()->set('flatpack.actions', [
            'edit' => Flatpack\Actions\Handlers\EditRecordHandler::class,
            'save' => Flatpack\Actions\Handlers\SaveRecordHandler::class,
            'delete' => Flatpack\Actions\Handlers\DeleteRecordHandler::class,
        ]);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->post(route('flatpack.entities.action', [
                'entity' => 'posts',
            ]), [
                'action' => 'create',
            ])
            ->assertNotFound();
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity update route rejects editable fields missing from fillable', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-update-fillable-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  title:
    label: Title
    type: text
    editable: true
  content:
    label: Content
    type: textarea
    editable: true
YAML);
        config()->set('flatpack.path', $tempPath);

        /** @var Post $post */
        $post = Post::factory()->create([
            'title' => 'Original title',
            'body' => 'Original body',
        ]);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->from(route('flatpack.entities.index', ['entity' => 'posts']))
            ->patch(route('flatpack.entities.update', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]), [
                'field' => 'content',
                'value' => 'Updated content',
            ])
            ->assertSessionHasErrors('flatpack');

        expect($post->fresh()?->title)->toBe('Original title');
        expect($post->fresh()?->body)->toBe('Original body');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity list inline update rejects when policy denies update', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-update-policy-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  title:
    label: Title
    type: text
    editable: true
YAML);
        config()->set('flatpack.path', $tempPath);

        Gate::policy(Post::class, DenyUpdatePostPolicy::class);

        /** @var Post $post */
        $post = Post::factory()->create([
            'title' => 'Original title',
        ]);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->patch(route('flatpack.entities.update', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]), [
                'field' => 'title',
                'value' => 'Updated title',
            ])
            ->assertForbidden();

        expect($post->fresh()?->title)->toBe('Original title');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity row delete rejects when policy denies delete', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-row-delete-policy-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  title:
    label: Title
    type: text
YAML);
        config()->set('flatpack.path', $tempPath);

        Gate::policy(Post::class, DenyDeletePostPolicy::class);

        /** @var Post $post */
        $post = Post::factory()->create([
            'title' => 'Do not delete',
        ]);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->post(route('flatpack.entities.row-action', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]), [
                'action' => 'delete',
            ])
            ->assertForbidden();

        expect($post->fresh())->not->toBeNull();
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack bulk delete rejects when policy denies delete', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-bulk-delete-policy-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  title:
    label: Title
YAML);
        config()->set('flatpack.path', $tempPath);

        Gate::policy(Post::class, DenyDeletePostPolicy::class);

        $target = Post::factory()->create(['title' => 'Should remain']);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->post(route('flatpack.entities.bulk-action', [
                'entity' => 'posts',
            ]), [
                'action' => 'delete',
                'selection' => [(string) $target->getKey()],
            ])
            ->assertForbidden();

        expect($target->fresh())->not->toBeNull();
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

test('flatpack entity list JSON supports date filter without a matching column', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-date-filter-without-column-' . uniqid('', true);

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
filters:
  created_at:
    label: Filter by created at
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

        $payload = actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'filters' => ['created_at' => '2024-01-15'],
            ]))
            ->assertOk()
            ->json();

        $body = $payload['data'] ?? $payload;
        expect($body['records'])->toHaveCount(1);
        expect($body['records'][0]['title'])->toBe('Newer post');
        expect($body['filters'])->toHaveCount(1);
        expect($body['filters'][0])->toMatchArray([
            'id' => 'created_at',
            'type' => 'date',
            'mode' => 'from',
        ]);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity list JSON supports filter-level select options in map and list formats', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-filter-level-select-options-' . uniqid('', true);

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
filters:
  status:
    label: Filter by status
    placeholder: Select status
    type: select
    multiple: true
    options:
      active: Active
      inactive: Inactive
YAML);
        config()->set('flatpack.path', $tempPath);

        Post::factory()->create([
            'title' => 'Active post',
            'status' => 'active',
        ]);
        Post::factory()->create([
            'title' => 'Inactive post',
            'status' => 'inactive',
        ]);

        /** @var User $user */
        $user = User::factory()->createOne();

        $payload = actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'filters' => [
                    'status' => ['active', 'inactive'],
                ],
            ]))
            ->assertOk()
            ->json();

        $mapBody = $payload['data'] ?? $payload;
        expect($mapBody['records'])->toHaveCount(2);
        expect(collect($mapBody['records'])->pluck('title')->all())
            ->toContain('Active post')
            ->toContain('Inactive post');

        expect($mapBody['filters'])->toHaveCount(1);
        expect($mapBody['filters'][0]['id'])->toBe('status');
        expect($mapBody['filters'][0]['options'])->toBe([
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'inactive', 'label' => 'Inactive'],
        ]);

        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  id:
    label: ID
  title:
    label: Title
filters:
  status:
    label: Filter by status
    placeholder: Select status
    type: select
    options:
      - value: active
        label: Active
        status: success
        icon: circle-check
      - value: inactive
        label: Inactive
        status: warning
        icon: triangle-alert
      - value: draft
        label: Draft
        status: pending
        icon: loader-circle
YAML);

        $listPayload = actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'filters' => ['status' => 'active'],
            ]))
            ->assertOk()
            ->json();

        $listBody = $listPayload['data'] ?? $listPayload;
        expect($listBody['records'])->toHaveCount(1);
        expect($listBody['records'][0]['title'])->toBe('Active post');
        expect($listBody['filters'])->toHaveCount(1);
        expect($listBody['filters'][0]['id'])->toBe('status');
        expect($listBody['filters'][0]['options'])->toBe([
            [
                'value' => 'active',
                'label' => 'Active',
                'status' => 'success',
                'icon' => 'circle-check',
            ],
            [
                'value' => 'inactive',
                'label' => 'Inactive',
                'status' => 'warning',
                'icon' => 'triangle-alert',
            ],
            [
                'value' => 'draft',
                'label' => 'Draft',
                'status' => 'pending',
                'icon' => 'loader-circle',
            ],
        ]);
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

test('flatpack entity list JSON bulk delete removes selected ids', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-bulk-delete-ids-' . uniqid('', true);

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

        $keep = Post::factory()->create(['title' => 'Keep me']);
        $deleteA = Post::factory()->create(['title' => 'Delete me A']);
        $deleteB = Post::factory()->create(['title' => 'Delete me B']);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->from(route('flatpack.entities.index', ['entity' => 'posts']))
            ->post(route('flatpack.entities.bulk-action', [
                'entity' => 'posts',
            ]), [
                'action' => 'delete',
                'selection' => [(string) $deleteA->getKey(), (string) $deleteB->getKey()],
            ])
            ->assertStatus(303);

        expect(Post::query()->whereKey($keep->getKey())->exists())->toBeTrue();
        expect(Post::query()->whereKey($deleteA->getKey())->exists())->toBeFalse();
        expect(Post::query()->whereKey($deleteB->getKey())->exists())->toBeFalse();
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity list JSON only exposes configured bulk actions', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-bulk-actions-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
bulk_actions:
  delete:
    label: Delete
    action: delete
    variant: destructive
    success_message: Posts removed
    confirm: true
    success_redirect: list
  publish:
    label: Publish
    action: publish
    variant: primary
columns:
  id:
    label: ID
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
        expect($body['bulk_actions'])->toEqual([
            [
                'id' => 'delete',
                'label' => 'Delete',
                'action' => 'delete',
                'icon' => '',
                'variant' => 'destructive',
                'success_message' => 'Posts removed',
                'confirm' => true,
                'success_redirect' => 'list',
            ],
        ]);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity list JSON bulk delete supports select_all with filters', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-bulk-delete-all-' . uniqid('', true);

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
  status:
    label: Status
    type: select
    options:
      active: Active
      inactive: Inactive
filters:
  status:
    type: select
YAML);
        config()->set('flatpack.path', $tempPath);

        Post::factory()->create(['title' => 'Delete active alpha', 'status' => 'active']);
        Post::factory()->create(['title' => 'Delete active beta', 'status' => 'active']);
        Post::factory()->create(['title' => 'Keep inactive alpha', 'status' => 'inactive']);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->from(route('flatpack.entities.index', ['entity' => 'posts']))
            ->post(route('flatpack.entities.bulk-action', [
                'entity' => 'posts',
            ]), [
                'action' => 'delete',
                'selection' => 'all',
                'search' => 'alpha',
                'filters' => ['status' => 'active'],
            ])
            ->assertStatus(303);

        expect(Post::query()->where('title', 'Delete active alpha')->exists())->toBeFalse();
        expect(Post::query()->where('title', 'Delete active beta')->exists())->toBeTrue();
        expect(Post::query()->where('title', 'Keep inactive alpha')->exists())->toBeTrue();
    } finally {
        File::deleteDirectory($tempPath);
    }
});
