<?php

declare(strict_types=1);

use Flatpack\Services\Uploads\FileUploadBrowserUrl;
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

    $storage = new FileUploadStorage(new FileUploadBrowserUrl);
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

test('storeFile uses file_disk when disk omitted even in relation mode', function (): void {
    config()->set('flatpack.uploads.file_disk', 'local');
    config()->set('flatpack.uploads.media_disk', 'only_via_media_token');
    Storage::fake('local');

    $storage = new FileUploadStorage(new FileUploadBrowserUrl);
    $meta = $storage->storeFile([
        'mode' => 'relation',
        'relation' => 'files',
        'callback' => 'syncFiles',
    ], UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'));

    expect($meta['disk'])->toBe('local')
        ->and(Storage::disk('local')->exists($meta['path']))->toBeTrue();
});

test('storeFile resolves disk media to media_disk config', function (): void {
    config()->set('flatpack.uploads.media_disk', 'media_vol');
    Storage::fake('media_vol');

    $storage = new FileUploadStorage(new FileUploadBrowserUrl);
    $meta = $storage->storeFile([
        'mode' => 'url',
        'disk' => 'media',
    ], UploadedFile::fake()->create('note.pdf', 50, 'application/pdf'));

    expect($meta['disk'])->toBe('media_vol')
        ->and(Storage::disk('media_vol')->exists($meta['path']))->toBeTrue();
});
