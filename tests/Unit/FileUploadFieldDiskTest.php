<?php

declare(strict_types=1);

use Flatpack\Services\Uploads\FileUploadFieldDisk;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

test('resolve maps empty to file_disk config', function (): void {
    config()->set('flatpack.uploads.file_disk', 'from_config');

    expect(FileUploadFieldDisk::resolve(null))->toBe('from_config')
        ->and(FileUploadFieldDisk::resolve(''))->toBe('from_config')
        ->and(FileUploadFieldDisk::resolve('   '))->toBe('from_config');
});

test('resolve maps literal media to media_disk config', function (): void {
    config()->set('flatpack.uploads.media_disk', 'media_store');

    expect(FileUploadFieldDisk::resolve('media'))->toBe('media_store');
});

test('resolve passes through concrete disk names', function (): void {
    config()->set('flatpack.uploads.file_disk', 'ignored');

    expect(FileUploadFieldDisk::resolve('public'))->toBe('public')
        ->and(FileUploadFieldDisk::resolve('s3'))->toBe('s3');
});
