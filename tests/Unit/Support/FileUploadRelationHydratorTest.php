<?php

declare(strict_types=1);

use Flatpack\Support\FileUploadRelationHydrator;
use Flatpack\Tests\Models\Post;

it('builds upload fragment from related model attributes matching metadata keys', function (): void {
    $post = new Post;
    $post->forceFill([
        'disk' => 'public',
        'path' => 'uploads/a.png',
        'url' => '/storage/uploads/a.png',
        'name' => 'a.png',
        'mime_type' => 'image/png',
        'size' => 120,
    ]);

    expect(FileUploadRelationHydrator::fragmentFromRelatedModel($post))->toMatchArray([
        'disk' => 'public',
        'path' => 'uploads/a.png',
        'url' => '/storage/uploads/a.png',
        'name' => 'a.png',
        'mime_type' => 'image/png',
        'size' => 120,
    ]);
});

it('skips empty attributes', function (): void {
    $post = new Post;
    $post->forceFill([
        'path' => 'x.bin',
        'url' => '/storage/x.bin',
    ]);

    expect(FileUploadRelationHydrator::fragmentFromRelatedModel($post))->toBe([
        'path' => 'x.bin',
        'url' => '/storage/x.bin',
    ]);
});
