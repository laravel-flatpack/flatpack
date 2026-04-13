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
    } finally {
        File::deleteDirectory($tempPath);
    }
});
