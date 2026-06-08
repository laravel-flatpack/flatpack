<?php

declare(strict_types=1);

use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;
use Illuminate\Support\Facades\File;

uses(Flatpack\Tests\TestCase::class);

test('flatpack list header action rejects undeclared action with 422', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-allowlist-header-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
actions:
  create:
    label: Create
    action: create
columns:
  id:
    label: ID
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('flatpack.entities.action', ['entity' => 'posts']), [
                'action' => 'delete',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack list header action allows declared action', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-allowlist-header-ok-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
actions:
  create:
    label: Create
    action: create
columns:
  id:
    label: ID
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->post(route('flatpack.entities.action', ['entity' => 'posts']), [
                'action' => 'create',
            ])
            ->assertStatus(303);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack row action rejects undeclared action with 422', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-allowlist-row-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  actions:
    type: actions
    actions:
      - label: Edit
        action: edit
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        /** @var Post $post */
        $post = Post::factory()->createOne();

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('flatpack.entities.row-action', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]), [
                'action' => 'delete',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack row action allows declared column action', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-allowlist-row-ok-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  actions:
    type: actions
    actions:
      - label: Delete
        action: delete
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        /** @var Post $post */
        $post = Post::factory()->createOne();

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->post(route('flatpack.entities.row-action', [
                'entity' => 'posts',
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

test('flatpack bulk action rejects undeclared action with 422', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-allowlist-bulk-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
bulk_actions:
  delete:
    label: Delete
    action: delete
columns:
  id:
    label: ID
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        /** @var Post $post */
        $post = Post::factory()->createOne();

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('flatpack.entities.bulk-action', ['entity' => 'posts']), [
                'action' => 'restore',
                'selection' => [(string) $post->getKey()],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack bulk action allows declared bulk action for active tab', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-list-allowlist-bulk-ok-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
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
    bulkActions:
      delete:
        label: Delete drafts
        action: delete
columns:
  id:
    label: ID
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        $target = Post::factory()->create(['status' => 'draft']);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->post(route('flatpack.entities.bulk-action', ['entity' => 'posts']), [
                'action' => 'delete',
                'selection' => [(string) $target->getKey()],
                'tab' => 'drafts',
            ])
            ->assertStatus(303);

        expect(Post::query()->whereKey($target->getKey())->exists())->toBeFalse();
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack dashboard widget row action rejects undeclared action with 422', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-widget-allowlist-row-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/dashboard');
        File::put($tempPath . '/dashboard/list.yaml', <<<'YAML'
name: Overview
widgets:
  recent_posts:
    type: table
    model: Flatpack\Tests\Models\Post
    columns:
      actions:
        type: actions
        actions:
          - label: Edit
            action: edit
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        /** @var Post $post */
        $post = Post::factory()->createOne();

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('flatpack.dashboard.widgets.row-action', [
                'widget' => 'recent_posts',
                'record' => (string) $post->getKey(),
            ]), [
                'action' => 'delete',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    } finally {
        File::deleteDirectory($tempPath);
    }
});
