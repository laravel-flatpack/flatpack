<?php

declare(strict_types=1);

use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

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
        $category = Flatpack\Tests\Models\Category::factory()->createOne([
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
            ->assertJsonPath('schema.fields.category_id.options.0.value', (string) $category->getKey())
            ->assertJsonPath('schema.fields.category_id.options.0.label', 'Guides');
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
                    'user_id' => 123,
                ],
            ])
            ->assertRedirect(route('flatpack.entities.create', ['entity' => 'posts']))
            ->assertSessionHasErrors('flatpack');

        expect(
            Post::query()->where('slug', 'should-fail')->exists(),
        )->toBeFalse();
    });
});
