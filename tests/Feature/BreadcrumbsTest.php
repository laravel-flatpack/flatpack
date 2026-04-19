<?php

declare(strict_types=1);

use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(TestCase::class, RefreshDatabase::class);

test('flatpack shares breadcrumbs on login page', function () {
    $this->get(route('flatpack.login'))
        ->assertInertia(fn ($page) => $page
            ->has('flatpack.breadcrumbs', 1)
            ->where('flatpack.breadcrumbs.0.label', 'Login')
            ->where('flatpack.breadcrumbs.0.href', null)
        );
});

test('flatpack shares breadcrumbs for dashboard and entity list', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-bc-' . uniqid('', true);

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

        $this->actingAs($user)
            ->get(route('flatpack.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('flatpack.breadcrumbs', 1)
                ->where('flatpack.breadcrumbs.0.label', 'Dashboard')
                ->where('flatpack.breadcrumbs.0.href', null)
            );

        $this->actingAs($user)
            ->get(route('flatpack.entities.index', ['entity' => 'posts']))
            ->assertInertia(fn ($page) => $page
                ->has('flatpack.breadcrumbs', 2)
                ->where('flatpack.breadcrumbs.0.label', 'Dashboard')
                ->where('flatpack.breadcrumbs.0.href', route('flatpack.dashboard'))
                ->where('flatpack.breadcrumbs.1.label', 'Posts')
                ->where('flatpack.breadcrumbs.1.href', null)
            );
    } finally {
        File::deleteDirectory($tempPath);
    }
});
