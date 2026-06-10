<?php

declare(strict_types=1);

use Flatpack\Tests\Models\Category;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\Policies\DenyViewCategoryPolicy;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;

uses(TestCase::class, RefreshDatabase::class);

test('flatpack relation options returns 403 when related model policy denies viewAny', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-relation-options-deny-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/form.yaml', <<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  category_id:
    type: combobox
    label: Category
    relation: category
    relation_name: name
    relation_value: id
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        Gate::policy(Category::class, DenyViewCategoryPolicy::class);

        Category::factory()->createOne(['name' => 'News']);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.entities.relation-options', [
                'entity' => 'posts',
                'field' => 'category_id',
            ]))
            ->assertForbidden();
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack relation options returns 200 when related model policy allows viewAny', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-relation-options-allow-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/form.yaml', <<<'YAML'
name: Post
model: Flatpack\Tests\Models\Post
fields:
  category_id:
    type: combobox
    label: Category
    relation: category
    relation_name: name
    relation_value: id
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        Category::factory()->createOne(['name' => 'News']);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.entities.relation-options', [
                'entity' => 'posts',
                'field' => 'category_id',
            ]))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack embedded table relation options returns 403 when related model policy denies viewAny', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-embedded-relation-options-deny-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/form.yaml', <<<'YAML'
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
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        Gate::policy(User::class, DenyViewCategoryPolicy::class);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.entities.embedded-table-relation-options', [
                'entity' => 'posts',
                'table_field' => 'comments',
                'column_id' => 'user_id',
            ]))
            ->assertForbidden();
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack dashboard widget relation options returns 403 when related model policy denies viewAny', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-widget-relation-options-deny-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/dashboard');
        File::put($tempPath . '/dashboard/list.yaml', <<<'YAML'
name: Overview
widgets:
  posts_table:
    type: table
    model: Flatpack\Tests\Models\Post
    columns:
      category_id:
        type: relation
        label: Category
        relation: category
        relation_name: name
        relation_value: id
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        Gate::policy(Category::class, DenyViewCategoryPolicy::class);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.dashboard.widgets.relation-options', [
                'widget' => 'posts_table',
                'column_id' => 'category_id',
            ]))
            ->assertForbidden();
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack dashboard widget relation options returns 200 when related model policy allows viewAny', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-widget-relation-options-allow-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/dashboard');
        File::put($tempPath . '/dashboard/list.yaml', <<<'YAML'
name: Overview
widgets:
  posts_table:
    type: table
    model: Flatpack\Tests\Models\Post
    columns:
      category_id:
        type: relation
        label: Category
        relation: category
        relation_name: name
        relation_value: id
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        Category::factory()->createOne(['name' => 'News']);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.dashboard.widgets.relation-options', [
                'widget' => 'posts_table',
                'column_id' => 'category_id',
            ]))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    } finally {
        File::deleteDirectory($tempPath);
    }
});
