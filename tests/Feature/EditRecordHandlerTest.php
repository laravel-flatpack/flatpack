<?php

declare(strict_types=1);

use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\actingAs;

uses(TestCase::class, RefreshDatabase::class);

test('flatpack entity row edit action redirects to the edit form', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-row-edit-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
columns:
  title:
    label: Title
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
                'action' => 'edit',
            ])
            ->assertStatus(303)
            ->assertRedirect(route('flatpack.entities.edit', [
                'entity' => 'posts',
                'record' => (string) $post->getKey(),
            ]));
    } finally {
        File::deleteDirectory($tempPath);
    }
});
