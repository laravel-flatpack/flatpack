<?php

declare(strict_types=1);

use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Tests\Models\Category;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\PostComment;
use Flatpack\Tests\Models\PostMeta;
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

test('flatpack entity create form save respects save_and_quit action key success_redirect list', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
actions:
  save:
    label: Save
    action: save
  save_and_quit:
    label: Save and quit
    action: save
    success_redirect: list
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
                    'title' => 'Via save_and_quit',
                    'slug' => 'via-save-and-quit',
                ],
                'form_action_id' => 'save_and_quit',
            ])
            ->assertRedirect(route('flatpack.entities.index', ['entity' => 'posts']));

        expect(
            Post::query()->where('title', 'Via save_and_quit')->exists(),
        )->toBeTrue();
    });
});

test('flatpack entity create form save respects actions.save success_redirect list', function () {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
actions:
  save:
    label: Save
    action: save
    success_redirect: list
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
                    'title' => 'Redirect list',
                    'slug' => 'redirect-list',
                ],
            ])
            ->assertRedirect(route('flatpack.entities.index', ['entity' => 'posts']));

        expect(
            Post::query()->where('title', 'Redirect list')->exists(),
        )->toBeTrue();
    });
});

test('flatpack entity form save rejects empty values with nothing to save message', function () {
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

        actingAs($user)
            ->from(route('flatpack.entities.create', ['entity' => 'posts']))
            ->post(route('flatpack.entities.store', ['entity' => 'posts']), [
                'values' => [],
            ])
            ->assertSessionHasErrors(['values' => 'Nothing to save']);
    });
});

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

test('flatpack entity edit GET inertia props include values persisted by PATCH', function () {
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

        actingAs($user)
            ->get(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('form', false)
                ->where('values.title', 'Updated title'));
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
    success_message: Post saved successfully
    confirm: true
    success_redirect: list
fields:
  published_at:
    type: date
    label: Published At
  category_id:
    type: combobox
    label: Category
    relation: category
    relation_name: name
    relation_value: id
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        Category::factory()->createOne([
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
            ->assertJsonPath('form_actions.0.success_message', 'Post saved successfully')
            ->assertJsonPath('form_actions.0.confirm', true)
            ->assertJsonPath('form_actions.0.success_redirect', 'list')
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
    type: combobox
    label: Category
    relation: category
    relation_name: name
    relation_value: id
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        Category::factory()->createMany([
            ['name' => 'Alpha'],
            ['name' => 'Beta'],
            ['name' => 'Gamma'],
        ]);
        $selected = Category::factory()->createOne([
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

test('embedded table relation column returns user options for the child model', function (): void {
    withTempFormSchema(<<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  comments:
    type: table
    label: Comments
    relation: comments
    columns:
      user_id:
        type: relation
        label: User
        relation: user
        relation_name: name
        relation_value: id
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        User::factory()->createOne(['name' => 'Picker One']);
        User::factory()->createOne(['name' => 'Picker Two']);

        actingAs($user)
            ->getJson(route('flatpack.entities.embedded-table-relation-options', [
                'entity' => 'posts',
                'table_field' => 'comments',
                'column_id' => 'user_id',
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertJsonPath('meta.per_page', 10);
    });
});

test('flatpack entity form save reports mass assignment failures as validation errors', function () {
    config(['flatpack.log_form_save_failures' => true]);

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
            ->assertSessionHasErrors([
                'flatpack',
                'flatpack_exception',
                'flatpack_exception_message',
            ]);

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

            public function allows(
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
    type: combobox
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

test('flatpack form edit hydrates single relation combobox when field key equals relation name', function () {
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
  category:
    type: combobox
    label: Category
    relation: category
    relation_name: name
    relation_value: id
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        $category = Category::factory()->createOne([
            'name' => 'Guides',
            'slug' => 'guides',
        ]);
        $post = Post::factory()->createOne([
            'title' => 'Hydrated title',
            'slug' => 'hydrated-title',
            'category_id' => $category->getKey(),
        ]);

        actingAs($user)
            ->getJson(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
                'json' => true,
            ]))
            ->assertOk()
            ->assertJsonPath('values.category', (string) $category->getKey());
    });
});

test('flatpack form save maps single relation combobox field key to belongsTo foreign key', function () {
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
  category:
    type: combobox
    label: Category
    relation: category
    relation_name: name
    relation_value: id
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        $first = Category::factory()->createOne(['name' => 'Alpha', 'slug' => 'alpha']);
        $next = Category::factory()->createOne(['name' => 'Beta', 'slug' => 'beta']);
        $post = Post::factory()->createOne([
            'title' => 'Original',
            'slug' => 'original',
            'category_id' => $first->getKey(),
        ]);

        actingAs($user)
            ->patch(route('flatpack.entities.save', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]), [
                'values' => [
                    'category' => (string) $next->getKey(),
                ],
            ])
            ->assertRedirect(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]));

        expect($post->fresh()?->category_id)->toBe($next->getKey());
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

test('flatpack form edit hydrates BelongsToMany table field as RelationRow list', function () {
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
  post_categories:
    type: table
    label: Categories
    relation: categories
    relation_value: id
    limit: 20
    columns:
      - id: name
        label: Name
        type: text
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        $a = Category::factory()->createOne(['name' => 'Alpha', 'slug' => 'alpha']);
        $b = Category::factory()->createOne(['name' => 'Beta', 'slug' => 'beta']);
        $post = Post::factory()->createOne([
            'title' => 'T',
            'slug' => 't',
        ]);
        $post->categories()->sync([$a->getKey(), $b->getKey()]);

        actingAs($user)
            ->getJson(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
                'json' => true,
            ]))
            ->assertOk()
            ->assertJsonPath('values.post_categories.0.id', (string) $a->getKey())
            ->assertJsonPath('values.post_categories.0.name', 'Alpha')
            ->assertJsonPath('values.post_categories.1.id', (string) $b->getKey())
            ->assertJsonPath('values.post_categories.1.name', 'Beta');
    });
});

test('flatpack form edit hydrates relation_name on multi combobox RelationRows without columns', function () {
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
  categories:
    type: combobox
    multiple: true
    label: Categories
    relation: categories
    relation_name: name
    relation_value: id
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        $a = Category::factory()->createOne(['name' => 'Alpha', 'slug' => 'alpha']);
        $b = Category::factory()->createOne(['name' => 'Beta', 'slug' => 'beta']);
        $post = Post::factory()->createOne([
            'title' => 'T',
            'slug' => 't',
        ]);
        $post->categories()->sync([$a->getKey(), $b->getKey()]);

        $response = actingAs($user)
            ->getJson(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
                'json' => true,
            ]))
            ->assertOk();

        $names = collect($response->json('values.categories'))
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        expect($names)->toBe(['Alpha', 'Beta']);
    });
});

test('flatpack form edit hydrates HasOne table field as single RelationRow list', function () {
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
  post_meta:
    type: table
    label: Meta
    relation: meta
    relation_value: id
    columns:
      subtitle:
        label: Subtitle
        type: text
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        $post = Post::factory()->createOne([
            'title' => 'T',
            'slug' => 't',
        ]);
        $meta = PostMeta::query()->create([
            'post_id' => $post->getKey(),
            'subtitle' => 'SEO subtitle',
        ]);

        actingAs($user)
            ->getJson(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
                'json' => true,
            ]))
            ->assertOk()
            ->assertJsonPath('values.post_meta.0.id', (string) $meta->getKey())
            ->assertJsonPath('values.post_meta.0.subtitle', 'SEO subtitle');
    });
});

test('flatpack form edit hydrates BelongsToMany table field with list.yaml map-shaped columns', function () {
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
  post_categories:
    type: table
    label: Categories
    relation: categories
    relation_value: id
    limit: 20
    columns:
      name:
        label: Name
        type: text
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        $a = Category::factory()->createOne(['name' => 'Alpha', 'slug' => 'alpha']);
        $b = Category::factory()->createOne(['name' => 'Beta', 'slug' => 'beta']);
        $post = Post::factory()->createOne([
            'title' => 'T',
            'slug' => 't',
        ]);
        $post->categories()->sync([$a->getKey(), $b->getKey()]);

        actingAs($user)
            ->getJson(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
                'json' => true,
            ]))
            ->assertOk()
            ->assertJsonPath('values.post_categories.0.id', (string) $a->getKey())
            ->assertJsonPath('values.post_categories.0.name', 'Alpha')
            ->assertJsonPath('values.post_categories.1.id', (string) $b->getKey())
            ->assertJsonPath('values.post_categories.1.name', 'Beta');
    });
});

test('flatpack form save syncs BelongsToMany RelationRow payload from table field', function () {
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
  post_categories:
    type: table
    label: Categories
    relation: categories
    relation_value: id
    columns:
      - id: name
        label: Name
        type: text
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        $keep = Category::factory()->createOne(['name' => 'Keep', 'slug' => 'keep']);
        $post = Post::factory()->createOne([
            'title' => 'T',
            'slug' => 't',
        ]);
        $post->categories()->sync([$keep->getKey()]);

        actingAs($user)
            ->patch(route('flatpack.entities.save', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]), [
                'values' => [
                    'title' => 'T',
                    'slug' => 't',
                    'post_categories' => [
                        ['id' => (string) $keep->getKey(), 'name' => 'Keep'],
                    ],
                ],
            ])
            ->assertRedirect(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]));

        expect($post->fresh()->categories->pluck('id')->all())->toBe([(int) $keep->getKey()]);
    });
});

test('flatpack form edit hydrates MorphMany table field as RelationRow list', function () {
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
  comments:
    type: table
    label: Comments
    relation: comments
    relation_value: id
    limit: 20
    columns:
      content:
        label: Content
        type: text
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        $post = Post::factory()->createOne([
            'title' => 'T',
            'slug' => 't',
        ]);
        $c1 = PostComment::factory()->createOne([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
            'content' => 'First',
        ]);
        $c2 = PostComment::factory()->createOne([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
            'content' => 'Second',
        ]);

        actingAs($user)
            ->getJson(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
                'json' => true,
            ]))
            ->assertOk()
            ->assertJsonPath('values.comments.0.id', (string) $c1->getKey())
            ->assertJsonPath('values.comments.0.content', 'First')
            ->assertJsonPath('values.comments.1.id', (string) $c2->getKey())
            ->assertJsonPath('values.comments.1.content', 'Second');
    });
});

test('flatpack form edit hydrates relation column nested payload for table rows', function () {
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
  comments:
    type: table
    label: Comments
    relation: comments
    relation_value: id
    columns:
      content:
        label: Content
        type: text
      user_id:
        label: User
        type: relation
        relation: user
        relation_name: name
        relation_value: id
YAML, function (): void {
        /** @var User $panelUser */
        $panelUser = User::factory()->createOne();
        /** @var User $author */
        $author = User::factory()->createOne(['name' => 'Ada Lovelace']);
        $post = Post::factory()->createOne([
            'title' => 'T',
            'slug' => 't',
        ]);
        PostComment::factory()->createOne([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
            'content' => 'Hello',
            'user_id' => $author->getKey(),
        ]);

        actingAs($panelUser)
            ->getJson(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
                'json' => true,
            ]))
            ->assertOk()
            ->assertJsonPath('values.comments.0.content', 'Hello')
            ->assertJsonPath('values.comments.0.user_id', $author->getKey())
            ->assertJsonPath('values.comments.0.user.id', (string) $author->getKey())
            ->assertJsonPath('values.comments.0.user.name', 'Ada Lovelace');
    });
});

test('flatpack form save syncs MorphMany RelationRow payload from table field', function () {
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
  comments:
    type: table
    label: Comments
    relation: comments
    relation_value: id
    columns:
      content:
        label: Content
        type: text
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        $post = Post::factory()->createOne([
            'title' => 'T',
            'slug' => 't',
        ]);
        $keep = PostComment::factory()->createOne([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
            'content' => 'Keep',
        ]);
        PostComment::factory()->createOne([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
            'content' => 'Remove me',
        ]);

        actingAs($user)
            ->patch(route('flatpack.entities.save', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]), [
                'values' => [
                    'title' => 'T',
                    'slug' => 't',
                    'comments' => [
                        ['id' => (string) $keep->getKey(), 'content' => 'Updated'],
                    ],
                ],
            ])
            ->assertRedirect(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]));

        $comments = $post->fresh()->comments()->orderBy('id')->get();
        expect($comments)->toHaveCount(1);
        expect($comments->first()->content)->toBe('Updated');
    });
});

test('flatpack form save syncs HasOne RelationRow payload from table field', function () {
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
  post_meta:
    type: table
    label: Meta
    relation: meta
    relation_value: id
    columns:
      subtitle:
        label: Subtitle
        type: text
YAML, function (): void {
        /** @var User $user */
        $user = User::factory()->createOne();
        $post = Post::factory()->createOne([
            'title' => 'T',
            'slug' => 't',
        ]);
        $meta = PostMeta::query()->create([
            'post_id' => $post->getKey(),
            'subtitle' => 'Old',
        ]);

        actingAs($user)
            ->patch(route('flatpack.entities.save', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]), [
                'values' => [
                    'title' => 'T',
                    'slug' => 't',
                    'post_meta' => [
                        ['id' => (string) $meta->getKey(), 'subtitle' => 'Updated subtitle'],
                    ],
                ],
            ])
            ->assertRedirect(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]));

        expect($post->fresh()->meta?->subtitle)->toBe('Updated subtitle');

        actingAs($user)
            ->patch(route('flatpack.entities.save', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]), [
                'values' => [
                    'title' => 'T',
                    'slug' => 't',
                    'post_meta' => [],
                ],
            ])
            ->assertRedirect(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]));

        expect($post->fresh()->meta)->toBeNull();
    });
});
