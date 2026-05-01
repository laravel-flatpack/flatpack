<?php

declare(strict_types=1);

use Flatpack\Services\Uploads\FileUploadStorage;
use Flatpack\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(TestCase::class);

test('storeFile stores on configured disk and returns metadata', function (
    array $fieldDefinition,
    string $expectedDisk,
) {
    Storage::fake($expectedDisk);

    $storage = new FileUploadStorage;
    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    $meta = $storage->storeFile($fieldDefinition, $file);

    expect($meta['disk'])->toBe($expectedDisk)
        ->and($meta['name'])->toBe('doc.pdf')
        ->and($meta)->toHaveKeys(['disk', 'path', 'url', 'name', 'mime_type', 'size', 'visibility'])
        ->and(Storage::disk($expectedDisk)->exists($meta['path']))->toBeTrue();
})->with([
    [['directory' => 'uploads', 'disk' => 'public'], 'public'],
    [['disk' => 'public'], 'public'],
]);
