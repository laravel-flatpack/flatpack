<?php

declare(strict_types=1);

use Flatpack\Composition\YamlCompositionLoader;
use Flatpack\Contracts\Composition\CompositionNotFoundException;
use Illuminate\Filesystem\Filesystem;

test('loads composition from a valid entity directory', function () {
    $filesystem = new Filesystem();
    $basePath = sys_get_temp_dir() . '/flatpack-yaml-loader-' . uniqid('', true);
    $entityPath = $basePath . '/posts';
    $filePath = $entityPath . '/list.yaml';

    $filesystem->ensureDirectoryExists($entityPath);
    $filesystem->put($filePath, "name: Posts\nmodel: App\\Models\\Post\n");

    try {
        $loader = new YamlCompositionLoader($filesystem, $basePath);
        $result = $loader->load('posts', 'list');

        expect($result)->toBe([
            'name' => 'Posts',
            'model' => 'App\\Models\\Post',
        ]);
    } finally {
        $filesystem->deleteDirectory($basePath);
    }
});

test('rejects entity names with traversal segments', function () {
    $filesystem = new Filesystem();
    $basePath = sys_get_temp_dir() . '/flatpack-yaml-loader-' . uniqid('', true);

    $filesystem->ensureDirectoryExists($basePath);

    try {
        $loader = new YamlCompositionLoader($filesystem, $basePath);

        expect(fn () => $loader->load('../secrets', 'list'))
            ->toThrow(CompositionNotFoundException::class);
    } finally {
        $filesystem->deleteDirectory($basePath);
    }
});

test('rejects symlinked entity directories outside base path', function () {
    if (! function_exists('symlink')) {
        test()->markTestSkipped('Symlink support is unavailable.');
    }

    $filesystem = new Filesystem();
    $testRoot = sys_get_temp_dir() . '/flatpack-yaml-loader-' . uniqid('', true);
    $basePath = $testRoot . '/base';
    $outsidePath = $testRoot . '/outside';
    $linkPath = $basePath . '/posts';

    $filesystem->ensureDirectoryExists($basePath);
    $filesystem->ensureDirectoryExists($outsidePath);
    $filesystem->put($outsidePath . '/list.yaml', "name: Outside\n");
    symlink($outsidePath, $linkPath);

    try {
        $loader = new YamlCompositionLoader($filesystem, $basePath);

        expect(fn () => $loader->load('posts', 'list'))
            ->toThrow(CompositionNotFoundException::class);
    } finally {
        $filesystem->deleteDirectory($testRoot);
    }
});
