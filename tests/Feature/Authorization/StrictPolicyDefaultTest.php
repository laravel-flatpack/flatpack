<?php

declare(strict_types=1);

use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\actingAs;

uses(TestCase::class, RefreshDatabase::class);

test('flatpack entity list JSON returns 403 when model has no policy and strict default applies', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-auth-strict-list-deny-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\UnregisteredPolicyPost
columns:
  id:
    label: ID
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        config(['flatpack.security.authorization.allow_when_policy_missing' => false]);

        Post::factory()->create(['title' => 'Hidden post']);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.entities.index', ['entity' => 'posts', 'json' => true]))
            ->assertForbidden();
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity list JSON returns 200 when model has no policy and allow_when_policy_missing is enabled', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-auth-strict-list-allow-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\UnregisteredPolicyPost
columns:
  id:
    label: ID
  title:
    label: Title
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        config(['flatpack.security.authorization.allow_when_policy_missing' => true]);

        Post::factory()->create(['title' => 'Visible post']);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->getJson(route('flatpack.entities.index', ['entity' => 'posts', 'json' => true]))
            ->assertOk()
            ->assertJsonPath('records.0.title', 'Visible post');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack bulk action returns 403 when model has no policy and strict default applies', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-auth-strict-bulk-deny-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\UnregisteredPolicyPost
bulk_actions:
  delete:
    label: Delete
    action: delete
columns:
  id:
    label: ID
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        config(['flatpack.security.authorization.allow_when_policy_missing' => false]);

        $post = Post::factory()->create(['title' => 'Keep me']);

        /** @var User $user */
        $user = User::factory()->createOne();

        actingAs($user)
            ->from(route('flatpack.entities.index', ['entity' => 'posts']))
            ->post(route('flatpack.entities.bulk-action', [
                'entity' => 'posts',
            ]), [
                'action' => 'delete',
                'selection' => [(string) $post->getKey()],
            ])
            ->assertForbidden();

        expect(Post::query()->whereKey($post->getKey())->exists())->toBeTrue();
    } finally {
        File::deleteDirectory($tempPath);
    }
});
