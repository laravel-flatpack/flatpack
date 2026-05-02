<?php

declare(strict_types=1);

use Flatpack\Composition\ModelClassEntitySlugResolver;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Support\Facades\File;

uses(TestCase::class);

test('ModelClassEntitySlugResolver returns entity directory whose list model matches', function () {
    $temp = sys_get_temp_dir() . '/flatpack-entity-slug-' . uniqid('', true);
    try {
        File::ensureDirectoryExists($temp . '/posts');
        File::put(
            $temp . '/posts/list.yaml',
            "name: Posts\nmodel: Flatpack\\Tests\\Models\\Post\n",
        );
        config()->set('flatpack.composition.path', $temp);

        $slug = app(ModelClassEntitySlugResolver::class)->firstEntitySlugForModel(Post::class);
        expect($slug)->toBe('posts');
    } finally {
        if (is_dir($temp)) {
            File::deleteDirectory($temp);
        }
    }
});

test('ModelClassEntitySlugResolver returns null when no list matches the model', function () {
    $temp = sys_get_temp_dir() . '/flatpack-entity-slug-' . uniqid('', true);
    try {
        File::ensureDirectoryExists($temp . '/other');
        File::put(
            $temp . '/other/list.yaml',
            "name: X\nmodel: Some\\Other\\Model\n",
        );
        config()->set('flatpack.composition.path', $temp);

        $slug = app(ModelClassEntitySlugResolver::class)->firstEntitySlugForModel(Post::class);
        expect($slug)->toBeNull();
    } finally {
        if (is_dir($temp)) {
            File::deleteDirectory($temp);
        }
    }
});
