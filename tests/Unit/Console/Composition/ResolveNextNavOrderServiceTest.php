<?php

declare(strict_types=1);

use Flatpack\Console\Composition\ResolveNextNavOrderService;
use Flatpack\Tests\TestCase;
use Illuminate\Support\Facades\File;

uses(TestCase::class);

test('resolve next nav order returns 100 for empty menu bucket', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-next-nav-empty-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath);
        config()->set('flatpack.composition.path', $tempPath);
        config()->set('flatpack.ui.navigation.main', null);
        config()->set('flatpack.ui.navigation.secondary', null);
        config()->set('flatpack.ui.navigation.bottom', null);

        $service = app(ResolveNextNavOrderService::class);
        expect($service->resolve('main'))->toBe(100);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('resolve next nav order uses max plus ten in selected bucket', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-next-nav-secondary-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::ensureDirectoryExists($tempPath . '/categories');
        config()->set('flatpack.composition.path', $tempPath);
        config()->set('flatpack.ui.navigation.main', null);
        config()->set('flatpack.ui.navigation.secondary', null);
        config()->set('flatpack.ui.navigation.bottom', null);

        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
nav_order: 50
menu: secondary
YAML);
        File::put($tempPath . '/categories/list.yaml', <<<'YAML'
name: Categories
nav_order: 70
menu: secondary
YAML);

        $service = app(ResolveNextNavOrderService::class);
        expect($service->resolve('secondary'))->toBe(80);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('resolve next nav order ignores entries from other menu buckets', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-next-nav-mixed-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::ensureDirectoryExists($tempPath . '/categories');
        config()->set('flatpack.composition.path', $tempPath);
        config()->set('flatpack.ui.navigation.main', null);
        config()->set('flatpack.ui.navigation.secondary', null);
        config()->set('flatpack.ui.navigation.bottom', null);

        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
nav_order: 90
menu: main
YAML);
        File::put($tempPath . '/categories/list.yaml', <<<'YAML'
name: Categories
nav_order: 30
menu: bottom
YAML);

        $service = app(ResolveNextNavOrderService::class);
        expect($service->resolve('bottom'))->toBe(40);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('resolve next nav order works in console even when list defines model class', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-next-nav-console-model-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        config()->set('flatpack.composition.path', $tempPath);
        config()->set('flatpack.ui.navigation.main', null);
        config()->set('flatpack.ui.navigation.secondary', null);
        config()->set('flatpack.ui.navigation.bottom', null);

        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
nav_order: 110
menu: main
YAML);

        $service = app(ResolveNextNavOrderService::class);
        expect($service->resolve('main'))->toBe(120);
    } finally {
        File::deleteDirectory($tempPath);
    }
});
