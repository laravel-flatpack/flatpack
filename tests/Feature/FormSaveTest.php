<?php

declare(strict_types=1);

use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\Policies\DenyCreatePostPolicy;
use Flatpack\Tests\Policies\DenyUpdatePostPolicy;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;

uses(Flatpack\Tests\TestCase::class, RefreshDatabase::class);

function withTempFormSchema(string $yaml, callable $callback): void
{
    $tempPath = sys_get_temp_dir() . '/flatpack-form-save-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/form.yaml', $yaml);
        config()->set('flatpack.path', $tempPath);

        $callback();
    } finally {
        File::deleteDirectory($tempPath);
    }
}

test('flatpack entity create form save creates a record and redirects to edit', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  title:
    type: text
    label: Title
  slug:
    type: text
    label: Slug
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->post(route('flatpack.entities.store', ['entity' => 'posts']), [
                'values' => [
                    'title' => 'Created from form submit',
                    'slug' => 'created-from-form-submit',
                ],
            ])
            ->assertRedirect(
                route('flatpack.entities.edit', [
                    'entity' => 'posts',
                    'record' => (string) Post::query()->latest('id')->value('id'),
                ]),
            );

        expect(
            Post::query()->where('title', 'Created from form submit')->exists(),
        )->toBeTrue();
    });
});

test('flatpack entity edit form save updates a record and redirects back to edit', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  title:
    type: text
    label: Title
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        $post = Post::factory()->createOne([
            'title' => 'Original title',
            'slug' => 'original-title',
        ]);

        actingAs($user)
            ->patch(route('flatpack.entities.save', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]), [
                'values' => [
                    'title' => 'Updated title',
                ],
            ])
            ->assertRedirect(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]));

        expect($post->fresh()?->title)->toBe('Updated title');
    });
});

test('flatpack entity edit form returns values for configured fields', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  title:
    type: text
    label: Title
  status:
    type: select
    label: Status
    options:
      - value: draft
        label: Draft
      - value: active
        label: Active
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        $post = Post::factory()->createOne([
            'title' => 'Hydrated title',
            'slug' => 'hydrated-title',
            'status' => 'active',
        ]);

        actingAs($user)
            ->getJson(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
                'json' => true,
            ]))
            ->assertOk()
            ->assertJsonPath('values.title', 'Hydrated title')
            ->assertJsonPath('values.status', 'active');
    });
});

test('flatpack entity create form returns normalized actions and field aliases', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
actions:
  save:
    label: Save
    action: save
    variant: primary
    icon: save
fields:
  published_at:
    type: date
    label: Published At
  category_id:
    type: relation
    label: Category
    relation: category
    relation_name: name
    relation_value: id
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        Flatpack\Tests\Models\Category::factory()->createOne([
            'name' => 'Guides',
        ]);

        actingAs($user)
            ->getJson(route('flatpack.entities.create', [
                'entity' => 'posts',
                'json' => true,
            ]))
            ->assertOk()
            ->assertJsonPath('form_actions.0.label', 'Save')
            ->assertJsonPath('form_actions.0.action', 'save')
            ->assertJsonPath('form_actions.0.variant', 'default')
            ->assertJsonPath('schema.fields.published_at.type', 'date-picker')
            ->assertJsonPath('schema.fields.category_id.type', 'combobox')
            ->assertJsonPath('schema.fields.category_id.remote', true)
            ->assertJsonPath('schema.fields.category_id.options', []);
    });
});

test('flatpack relation options endpoint returns paginated searchable options', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  category_id:
    type: relation
    label: Category
    relation: category
    relation_name: name
    relation_value: id
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        Flatpack\Tests\Models\Category::factory()->createMany([
            ['name' => 'Alpha'],
            ['name' => 'Beta'],
            ['name' => 'Gamma'],
        ]);
        $selected = Flatpack\Tests\Models\Category::factory()->createOne([
            'name' => 'Zeta',
        ]);

        actingAs($user)
            ->getJson(route('flatpack.entities.relation-options', [
                'entity' => 'posts',
                'field' => 'category_id',
                'q' => 'a',
                'per_page' => 2,
                'selected' => (string) $selected->getKey(),
            ]))
            ->assertOk()
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('data.0.value', (string) $selected->getKey())
            ->assertJsonPath('data.0.label', 'Zeta');
    });
});

test('flatpack entity form save reports mass assignment failures as validation errors', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  title:
    type: text
    label: Title
  slug:
    type: text
    label: Slug
  user_id:
    type: text
    label: User
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->from(route('flatpack.entities.create', ['entity' => 'posts']))
            ->post(route('flatpack.entities.store', ['entity' => 'posts']), [
                'values' => [
                    'title' => 'Should fail',
                    'slug' => 'should-fail',
                    'user_id' => '123',
                ],
            ])
            ->assertRedirect(route('flatpack.entities.create', ['entity' => 'posts']))
            ->assertSessionHasErrors('flatpack');

        expect(
            Post::query()->where('slug', 'should-fail')->exists(),
        )->toBeFalse();
    });
});

test('flatpack form store rejects users without panel access', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  title:
    type: text
    label: Title
  slug:
    type: text
    label: Slug
YAML, function (): void {
        $this->app->bind(FlatpackAuthorizer::class, fn (): FlatpackAuthorizer => new class implements FlatpackAuthorizer
        {
            public function canAccessPanel(?Authenticatable $user): bool
            {
                return false;
            }

            public function authorizeModelAbility(
                ?Authenticatable $user,
                string $ability,
                string $modelClass,
                ?object $model = null,
            ): bool {
                return true;
            }
        });

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->post(route('flatpack.entities.store', ['entity' => 'posts']), [
                'values' => [
                    'title' => 'Nope',
                    'slug' => 'nope',
                ],
            ])
            ->assertForbidden();
    });
});

test('flatpack form store rejects create when policy denies', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  title:
    type: text
    label: Title
  slug:
    type: text
    label: Slug
YAML, function (): void {
        Gate::policy(Post::class, DenyCreatePostPolicy::class);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->post(route('flatpack.entities.store', ['entity' => 'posts']), [
                'values' => [
                    'title' => 'Nope',
                    'slug' => 'nope',
                ],
            ])
            ->assertForbidden();
    });
});

test('flatpack form store rejects create when policy denies for inertia xhr', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  title:
    type: text
    label: Title
  slug:
    type: text
    label: Slug
YAML, function (): void {
        Gate::policy(Post::class, DenyCreatePostPolicy::class);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->post(
                route('flatpack.entities.store', ['entity' => 'posts']),
                [
                    'values' => [
                        'title' => 'Nope',
                        'slug' => 'nope',
                    ],
                ],
                [
                    'HTTP_X_INERTIA' => 'true',
                    'HTTP_ACCEPT' => 'text/html, application/xhtml+xml',
                ],
            )
            ->assertForbidden();
    });
});

test('flatpack form save rejects update when policy denies', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  title:
    type: text
    label: Title
YAML, function (): void {
        Gate::policy(Post::class, DenyUpdatePostPolicy::class);

        /** @var User $user */
        $user = User::factory()->createOne();
        $post = Post::factory()->createOne([
            'title' => 'Original title',
            'slug' => 'original-title',
        ]);

        actingAs($user)
            ->patch(route('flatpack.entities.save', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]), [
                'values' => [
                    'title' => 'Updated title',
                ],
            ])
            ->assertForbidden();
    });
});

test('flatpack form store validates required fields from yaml schema', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  title:
    type: text
    label: Title
    required: true
  slug:
    type: text
    label: Slug
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->from(route('flatpack.entities.create', ['entity' => 'posts']))
            ->post(route('flatpack.entities.store', ['entity' => 'posts']), [
                'values' => [
                    'title' => '',
                    'slug' => 'some-slug',
                ],
            ])
            ->assertRedirect(route('flatpack.entities.create', ['entity' => 'posts']))
            ->assertSessionHasErrors('values.title');
    });
});

test('flatpack form store validates relation exists rule', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  title:
    type: text
    label: Title
  slug:
    type: text
    label: Slug
  category_id:
    type: relation
    label: Category
    relation: category
    relation_name: name
    relation_value: id
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->from(route('flatpack.entities.create', ['entity' => 'posts']))
            ->post(route('flatpack.entities.store', ['entity' => 'posts']), [
                'values' => [
                    'title' => 'With bad category',
                    'slug' => 'with-bad-category',
                    'category_id' => '999999',
                ],
            ])
            ->assertRedirect(route('flatpack.entities.create', ['entity' => 'posts']))
            ->assertSessionHasErrors('values.category_id');
    });
});

test('flatpack form store merges yaml rules passthrough', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  title:
    type: text
    label: Title
    rules:
      - min:20
  slug:
    type: text
    label: Slug
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->from(route('flatpack.entities.create', ['entity' => 'posts']))
            ->post(route('flatpack.entities.store', ['entity' => 'posts']), [
                'values' => [
                    'title' => 'short',
                    'slug' => 'short-slug',
                ],
            ])
            ->assertRedirect(route('flatpack.entities.create', ['entity' => 'posts']))
            ->assertSessionHasErrors('values.title');
    });
});

test('flatpack form store merges yaml rules as pipe string', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  title:
    type: text
    label: Title
  slug:
    type: text
    label: Slug
    rules: string|max:4
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->from(route('flatpack.entities.create', ['entity' => 'posts']))
            ->post(route('flatpack.entities.store', ['entity' => 'posts']), [
                'values' => [
                    'title' => 'ok',
                    'slug' => 'toolong',
                ],
            ])
            ->assertRedirect(route('flatpack.entities.create', ['entity' => 'posts']))
            ->assertSessionHasErrors('values.slug');
    });
});
