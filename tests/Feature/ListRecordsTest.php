<?php

declare(strict_types=1);

use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\Policies\DenyDeletePostPolicy;
use Flatpack\Tests\Policies\DenyUpdatePostPolicy;
use Flatpack\Tests\Policies\DenyViewPostPolicy;
use Flatpack\Tests\Policies\SelectiveDeletePostPolicy;
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

        $body = $payload;
        expect($body['records'])->toBeArray();
        expect($body['records'][0]['title'])->toBe('Listed post');
        expect($body['pagination']['total'])->toBe(1);
        expect($body['pagination']['current_page'])->toBe(1);
        expect($body['model_key'])->toBe('id');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack list tabs apply model scopes and expose active tab id', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-tabs-scope-' . uniqid('', true);

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
tabs:
  records:
    label: Records
  trashed:
    label: Trashed
    scope: trashed
  draft:
    label: Draft
    scope: draftOnly
YAML);
        config()->set('flatpack.path', $tempPath);

        Post::factory()->create(['title' => 'Live one', 'status' => 'active']);
        Post::factory()->create(['title' => 'Draft one', 'status' => 'draft']);
        $deleted = Post::factory()->create(['title' => 'Deleted one', 'status' => 'active']);
        $deleted->delete();

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'tab' => 'trashed',
            ]))
            ->assertOk()
            ->assertJsonPath('active_tab', 'trashed')
            ->assertJsonCount(1, 'records')
            ->assertJsonPath('records.0.title', 'Deleted one');

        actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'tab' => 'draft',
            ]))
            ->assertOk()
            ->assertJsonPath('active_tab', 'draft')
            ->assertJsonCount(1, 'records')
            ->assertJsonPath('records.0.title', 'Draft one');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack list tab with missing scope returns validation error', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-tabs-invalid-scope-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  id:
    label: ID
tabs:
  records:
    label: Records
  drafts:
    label: Drafts
    scope: missingScope
YAML);
        config()->set('flatpack.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'tab' => 'drafts',
            ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.flatpack.0', 'Tab "drafts" references missing scope "missingScope" on Flatpack\\Tests\\Models\\Post.');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack list tabs can override root columns for the selected tab', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-tabs-columns-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  title:
    label: Title
tabs:
  records:
    label: Records
  status_only:
    label: Status only
    columns:
      status:
        label: Status
        type: text
YAML);
        config()->set('flatpack.path', $tempPath);

        Post::factory()->create(['title' => 'Scoped title', 'status' => 'draft']);

        /** @var User $user */
        $user = User::factory()->createOne();

        $payload = actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'tab' => 'status_only',
            ]))
            ->assertOk()
            ->assertJsonPath('active_tab', 'status_only')
            ->json();

        expect($payload['records'][0])->toHaveKey('status');
        expect($payload['records'][0])->not->toHaveKey('title');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack list tabs can override filters and bulk actions', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-tabs-filters-bulk-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
reorderable: true
row_click: edit_page
columns:
  title:
    label: Title
    type: text
filters:
  status:
    type: select
    options:
      - value: draft
        label: Draft
bulk_actions:
  delete:
    label: Delete
    action: delete
tabs:
  records:
    label: Records
  drafts:
    label: Drafts
    scope: draftOnly
    reorderable: false
    row_click: none
    filters:
      status:
        type: select
        options:
          - value: draft
            label: Draft
    bulkActions:
      delete:
        label: Delete selected drafts
        action: delete
YAML);
        config()->set('flatpack.path', $tempPath);

        Post::factory()->create(['title' => 'D1', 'status' => 'draft']);

        /** @var User $user */
        $user = User::factory()->createOne();

        $payload = actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'tab' => 'drafts',
            ]))
            ->assertOk()
            ->json();

        expect($payload['active_tab'])->toBe('drafts')
            ->and($payload['schema']['reorderable'])->toBeFalse()
            ->and($payload['schema']['row_click'])->toBe('none')
            ->and($payload['filters'][0]['id'])->toBe('status')
            ->and($payload['bulk_actions'])->toHaveCount(1)
            ->and($payload['bulk_actions'][0]['action'])->toBe('delete');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('scoped tabs default to empty filters and bulk actions while unscoped tabs inherit root', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-tabs-default-filters-bulk-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  title:
    label: Title
    type: text
filters:
  status:
    type: select
    options:
      - value: draft
        label: Draft
bulk_actions:
  delete:
    label: Delete
    action: delete
tabs:
  records:
    label: Records
  drafts:
    label: Drafts
    scope: draftOnly
YAML);
        config()->set('flatpack.path', $tempPath);

        Post::factory()->create(['title' => 'D1', 'status' => 'draft']);

        /** @var User $user */
        $user = User::factory()->createOne();

        $recordsPayload = actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'tab' => 'records',
            ]))
            ->assertOk()
            ->json();

        expect($recordsPayload['active_tab'])->toBe('records')
            ->and($recordsPayload['filters'])->toHaveCount(1)
            ->and($recordsPayload['bulk_actions'])->toHaveCount(1);

        $draftsPayload = actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
                'tab' => 'drafts',
            ]))
            ->assertOk()
            ->json();

        expect($draftsPayload['active_tab'])->toBe('drafts')
            ->and($draftsPayload['filters'])->toBe([])
            ->and($draftsPayload['bulk_actions'])->toBe([]);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack list with scope tabs and root columns still renders normalized columns', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-tabs-root-columns-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
reorderable: true
columns:
  title:
    label: Title
    type: text
    sortable: true
  content:
    label: Content
    type: textarea
    sortable: true
  actions:
    label: Actions
    type: actions
    actions:
      - label: Edit
        action: edit
tabs:
  records:
    label: All records
    icon: list
  trash:
    label: Trash
    icon: trash
    scope: trashed
    columns:
      title:
        label: Title
        sortable: true
      deleted_at:
        label: Deleted At
        type: date
        sortable: true
  drafts:
    label: Drafts
    icon: file-text
    scope: draft
YAML);
        config()->set('flatpack.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();

        $payload = actingAs($user)
            ->getJson(route('flatpack.entities.index', [
                'entity' => 'posts',
                'json' => true,
            ]))
            ->assertOk()
            ->json();

        expect($payload['schema']['columns'])->not->toBeEmpty()
            ->and($payload['schema']['tab_panels'])->toHaveCount(3)
            ->and($payload['schema']['tab_panels'][0]['column_ids'])->toBe(['title', 'content', 'actions']);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity list JSON rejects users when policy denies viewAny', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-viewany-deny-' . uniqid('', true);

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
        Gate::policy(Post::class, DenyViewPostPolicy::class);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.entities.index', ['entity' => 'posts', 'json' => true]))
            ->assertForbidden();
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

        $body = $payload;
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

        $body = $payload;
        expect($body['list_actions'])->toEqual([
            [
                'id' => 'create',
                'label' => 'Create',
                'icon' => '',
                'action' => 'create',
                'variant' => 'default',
                'primary' => true,
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

        $body = $payload;
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

test('flatpack entity list inline update validates select column values from list yaml', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-select-validation-' . uniqid('', true);

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
  status:
    label: Status
    type: select
    editable: true
    options:
      draft: Draft
      active: Active
YAML);
        config()->set('flatpack.path', $tempPath);

        /** @var Post $post */
        $post = Post::factory()->create([
            'title' => 'Hello',
            'status' => 'draft',
        ]);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->from(route('flatpack.entities.index', ['entity' => 'posts']))
            ->patch(route('flatpack.entities.update', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]), [
                'field' => 'status',
                'value' => 'not-an-option',
            ])
            ->assertRedirect(route('flatpack.entities.index', ['entity' => 'posts']))
            ->assertSessionHasErrors('values.status');

        expect($post->fresh()?->status)->toBe('draft');
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

        $body = $payload;
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

        $body = $payload;
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
        $fromBody = $fromPayload;
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
        $exactBody = $exactPayload;
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

        $body = $payload;
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

        $mapBody = $payload;
        expect($mapBody['records'])->toHaveCount(2);
        expect(collect($mapBody['records'])->pluck('title')->all())
            ->toContain('Active post')
            ->toContain('Inactive post');

        expect($mapBody['filters'])->toHaveCount(1);
        expect($mapBody['filters'][0]['id'])->toBe('status');
        expect($mapBody['filters'][0]['options'])->toHaveCount(2);
        expect($mapBody['filters'][0]['options'][0])->toMatchArray([
            'value' => 'active',
            'label' => 'Active',
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

        $listBody = $listPayload;
        expect($listBody['records'])->toHaveCount(1);
        expect($listBody['records'][0]['title'])->toBe('Active post');
        expect($listBody['filters'])->toHaveCount(1);
        expect($listBody['filters'][0]['id'])->toBe('status');
        expect($listBody['filters'][0]['options'])->not->toBeEmpty();
        expect(collect($listBody['filters'][0]['options'])->pluck('value')->all())
            ->toContain('active');
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

        $body = $payload;
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

test('flatpack bulk action denies when model policy is missing by default', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-bulk-delete-no-policy-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\PostBySlug
columns:
  slug:
    label: Slug
YAML);
        config()->set('flatpack.path', $tempPath);
        config()->set('flatpack.authorization.allow_when_policy_missing', false);

        Post::factory()->create(['slug' => 'one', 'title' => 'Keep me']);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->from(route('flatpack.entities.index', ['entity' => 'posts']))
            ->post(route('flatpack.entities.bulk-action', [
                'entity' => 'posts',
            ]), [
                'action' => 'delete',
                'selection' => ['one'],
            ])
            ->assertForbidden();

        expect(Post::query()->where('slug', 'one')->exists())->toBeTrue();
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

        $body = $payload;
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

test('flatpack bulk delete only deletes rows authorized by policy', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-bulk-delete-policy-scope-' . uniqid('', true);

    try {
        Gate::policy(Post::class, SelectiveDeletePostPolicy::class);
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

        $allowed = Post::factory()->create(['title' => 'delete me']);
        $denied = Post::factory()->create(['title' => 'protected row']);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->from(route('flatpack.entities.index', ['entity' => 'posts']))
            ->post(route('flatpack.entities.bulk-action', [
                'entity' => 'posts',
            ]), [
                'action' => 'delete',
                'selection' => [(string) $allowed->getKey(), (string) $denied->getKey()],
            ])
            ->assertStatus(303);

        expect(Post::query()->whereKey($allowed->getKey())->exists())->toBeFalse();
        expect(Post::query()->whereKey($denied->getKey())->exists())->toBeTrue();
    } finally {
        File::deleteDirectory($tempPath);
    }
});
