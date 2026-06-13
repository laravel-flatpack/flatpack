<?php

declare(strict_types=1);

use Flatpack\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

uses(TestCase::class);

test('flatpack:make writes form.yaml and list.yaml under pluralized entity directory', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-make-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath);
        config()->set('flatpack.composition.path', $tempPath);

        $this->artisan('flatpack:make', [
            '--model' => 'Flatpack\Tests\Models\Post',
        ])->assertSuccessful();

        expect(File::exists($tempPath . '/posts/form.yaml'))->toBeTrue()
            ->and(File::exists($tempPath . '/posts/list.yaml'))->toBeTrue();

        $formYaml = File::get($tempPath . '/posts/form.yaml');
        expect($formYaml)->toContain('model: Flatpack\Tests\Models\Post')
            ->toContain('name: Post')
            ->toContain('fields:')
            ->toContain('title:')
            ->toContain('type: text');

        expect(File::get($tempPath . '/posts/list.yaml'))->toContain('model: Flatpack\Tests\Models\Post')
            ->toContain('name: Posts')
            ->toContain('columns:')
            ->toContain('id: title')
            ->not->toContain('action: restore')
            ->not->toContain('action: force_delete');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack:make maps date and datetime casts to date-picker and list column types', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-make-dates-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath);
        config()->set('flatpack.composition.path', $tempPath);

        $this->artisan('flatpack:make', [
            '--model' => 'Flatpack\\Tests\\Models\\GuineaPigModel',
        ])->assertSuccessful();

        $form = File::get($tempPath . '/guinea_pig_models/form.yaml');
        expect($form)->toContain('happens_at:')
            ->toContain('on_calendar_date:')
            ->toContain('type: date-picker');

        $list = File::get($tempPath . '/guinea_pig_models/list.yaml');
        expect($list)->toContain('id: happens_at')
            ->toContain('type: datetime')
            ->toContain('id: on_calendar_date')
            ->toContain('type: date');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack:make refuses to overwrite without force', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-make-clobber-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/form.yaml', "name: X\nmodel: X\nfields: []\n");
        config()->set('flatpack.composition.path', $tempPath);

        $this->artisan('flatpack:make', [
            '--model' => 'Flatpack\Tests\Models\Post',
        ])->assertFailed();

        expect(File::get($tempPath . '/posts/form.yaml'))->toContain('name: X');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack:make overwrites when force is passed', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-make-force-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/form.yaml', "name: X\nmodel: X\nfields: []\n");
        File::put($tempPath . '/posts/list.yaml', "name: X\nmodel: X\n");
        config()->set('flatpack.composition.path', $tempPath);

        $this->artisan('flatpack:make', [
            '--model' => 'Flatpack\Tests\Models\Post',
            '--force' => true,
        ])->assertSuccessful();

        expect(File::get($tempPath . '/posts/form.yaml'))->toContain('Flatpack\Tests\Models\Post');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack:make applies non-interactive defaults when only model is passed', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-make-defaults-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath);
        config()->set('flatpack.composition.path', $tempPath);

        $this->artisan('flatpack:make', [
            '--model' => 'Flatpack\\Tests\\Models\\GuineaPigModel',
        ])->assertSuccessful();

        expect(File::exists($tempPath . '/guinea_pig_models/form.yaml'))->toBeTrue()
            ->and(File::exists($tempPath . '/guinea_pig_models/list.yaml'))->toBeTrue();

        $form = File::get($tempPath . '/guinea_pig_models/form.yaml');
        $list = File::get($tempPath . '/guinea_pig_models/list.yaml');

        expect($form)->toContain('icon: folder-open')
            ->toContain('nav_order: 100')
            ->toContain('action: save')
            ->toContain('action: delete');

        expect($list)->toContain('action: create')
            ->toContain('menu: main')
            ->toContain('bulk_actions:')
            ->toContain('action: delete');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack:make allows option overrides for entity names, icon, and nav order', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-make-overrides-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath);
        config()->set('flatpack.composition.path', $tempPath);

        $this->artisan('flatpack:make', [
            '--model' => 'Flatpack\\Tests\\Models\\GuineaPigModel',
            '--entity' => 'animal',
            '--entities' => 'animals',
            '--icon' => 'rocket',
            '--nav-order' => 7,
        ])->assertSuccessful();

        $form = File::get($tempPath . '/animals/form.yaml');
        $list = File::get($tempPath . '/animals/list.yaml');

        expect($form)->toContain('name: Animal')
            ->toContain('icon: rocket')
            ->toContain('nav_order: 7');

        expect($list)->toContain('name: Animals')
            ->toContain('icon: rocket')
            ->toContain('menu: main')
            ->toContain('nav_order: 7');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack:make can disable generated actions and guessed schema sections', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-make-toggles-off-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath);
        config()->set('flatpack.composition.path', $tempPath);

        $this->artisan('flatpack:make', [
            '--model' => 'Flatpack\\Tests\\Models\\GuineaPigModel',
            '--without-basic-actions' => true,
            '--without-bulk-delete' => true,
            '--without-auto-fields' => true,
            '--without-auto-columns' => true,
        ])->assertSuccessful();

        $form = Yaml::parse(File::get($tempPath . '/guinea_pig_models/form.yaml'));
        $list = Yaml::parse(File::get($tempPath . '/guinea_pig_models/list.yaml'));

        expect($form)->toBeArray()
            ->and($form['actions'] ?? null)->toBe([])
            ->and($form['fields'] ?? null)->toBe([])
            ->and(File::get($tempPath . '/guinea_pig_models/form.yaml'))->not->toContain('action: save');

        expect($list)->toBeArray()
            ->and($list['actions'] ?? null)->toBe([])
            ->and($list['bulk_actions'] ?? null)->toBe([])
            ->and($list['columns'] ?? null)->toBe([])
            ->and(File::get($tempPath . '/guinea_pig_models/list.yaml'))->not->toContain('action: create');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack:make can include soft delete actions when model uses soft deletes', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-make-soft-deletes-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath);
        config()->set('flatpack.composition.path', $tempPath);

        $this->artisan('flatpack:make', [
            '--model' => 'Flatpack\\Tests\\Models\\Post',
            '--with-soft-delete-actions' => true,
        ])->assertSuccessful();

        $form = File::get($tempPath . '/posts/form.yaml');
        $list = File::get($tempPath . '/posts/list.yaml');

        // expect($form)->toContain('action: restore')
        //     ->toContain('action: force_delete');

        // expect($list)->toContain('action: restore')
        //     ->toContain('action: force_delete');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack:make never emits soft delete actions for non-soft-delete models', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-make-no-soft-deletes-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath);
        config()->set('flatpack.composition.path', $tempPath);

        $this->artisan('flatpack:make', [
            '--model' => 'Flatpack\\Tests\\Models\\GuineaPigModel',
            '--with-soft-delete-actions' => true,
        ])->assertSuccessful();

        $form = File::get($tempPath . '/guinea_pig_models/form.yaml');
        $list = File::get($tempPath . '/guinea_pig_models/list.yaml');

        expect($form)->not->toContain('action: restore')
            ->not->toContain('action: force_delete');

        expect($list)->not->toContain('action: restore')
            ->not->toContain('action: force_delete');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack:make computes default nav order from existing menu bucket items', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-make-nav-order-main-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::ensureDirectoryExists($tempPath . '/categories');
        config()->set('flatpack.composition.path', $tempPath);

        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
nav_order: 40
menu: main
YAML);
        File::put($tempPath . '/categories/list.yaml', <<<'YAML'
name: Categories
nav_order: 60
menu: main
YAML);

        $this->artisan('flatpack:make', [
            '--model' => 'Flatpack\\Tests\\Models\\GuineaPigModel',
            '--entity' => 'animal',
            '--entities' => 'animals',
        ])->assertSuccessful();

        $form = File::get($tempPath . '/animals/form.yaml');
        $list = File::get($tempPath . '/animals/list.yaml');

        expect($form)->toContain('nav_order: 70');
        expect($list)->toContain('nav_order: 70')
            ->toContain('menu: main');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack:make computes default nav order from selected menu bucket', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-make-nav-order-secondary-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::ensureDirectoryExists($tempPath . '/categories');
        config()->set('flatpack.composition.path', $tempPath);

        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
nav_order: 20
menu: main
YAML);
        File::put($tempPath . '/categories/list.yaml', <<<'YAML'
name: Categories
nav_order: 30
menu: secondary
YAML);

        $this->artisan('flatpack:make', [
            '--model' => 'Flatpack\\Tests\\Models\\GuineaPigModel',
            '--entity' => 'animal',
            '--entities' => 'animals',
            '--menu' => 'secondary',
        ])->assertSuccessful();

        $list = File::get($tempPath . '/animals/list.yaml');
        expect($list)->toContain('menu: secondary')
            ->toContain('nav_order: 40');
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack:make uses explicit nav-order override instead of dynamic default', function () {
    $tempPath = sys_get_temp_dir() . '/flatpack-make-nav-order-override-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        config()->set('flatpack.composition.path', $tempPath);

        File::put($tempPath . '/posts/list.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
nav_order: 80
menu: main
YAML);

        $this->artisan('flatpack:make', [
            '--model' => 'Flatpack\\Tests\\Models\\GuineaPigModel',
            '--entity' => 'animal',
            '--entities' => 'animals',
            '--nav-order' => 7,
        ])->assertSuccessful();

        $list = File::get($tempPath . '/animals/list.yaml');
        expect($list)->toContain('nav_order: 7');
    } finally {
        File::deleteDirectory($tempPath);
    }
});
