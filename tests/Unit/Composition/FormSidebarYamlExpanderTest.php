<?php

declare(strict_types=1);

use Flatpack\Composition\FormSidebarYamlExpander;
use Flatpack\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;

uses(TestCase::class);

it('leaves schema unchanged when sidebar is not a non-empty string', function (): void {
    $expander = new FormSidebarYamlExpander(new Filesystem, app('config'));
    $schema = ['sidebar' => ['widgets' => []]];

    expect($expander->expand('posts', $schema))->toBe($schema);
});

it('loads a sidebar yaml fragment relative to the entity directory', function (): void {
    $tmp = sys_get_temp_dir() . '/flatpack-sidebar-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tmp . '/posts');
        File::put($tmp . '/posts/sidebar-frag.yaml', <<<'YAML'
widgets:
  revenue:
    type: metric
    provider: total_revenue
YAML);
        config()->set('flatpack.composition.path', $tmp);

        $expander = new FormSidebarYamlExpander(new Filesystem, app('config'));
        $out = $expander->expand('posts', [
            'sidebar' => 'sidebar-frag.yaml',
        ]);

        expect($out['sidebar'])->toBeArray()
            ->and($out['sidebar']['widgets']['revenue']['type'])->toBe('metric');
    } finally {
        File::deleteDirectory($tmp);
    }
});

it('returns original schema when fragment path is unsafe', function (): void {
    $expander = new FormSidebarYamlExpander(new Filesystem, app('config'));
    $schema = ['sidebar' => '../outside.yaml'];

    expect($expander->expand('posts', $schema))->toBe($schema);
});
