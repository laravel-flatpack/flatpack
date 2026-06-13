<?php

declare(strict_types=1);

use Flatpack\Services\Uploads\FileUploadDiskVisibility;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

it('uses field visibility when set', function (): void {
    config()->set('filesystems.disks.custom', [
        'driver' => 'local',
        'root' => '/tmp',
        'visibility' => 'private',
    ]);

    expect(FileUploadDiskVisibility::resolve('custom', [
        'visibility' => 'public',
    ]))->toBe('public');
});

it('uses disk filesystem visibility when field omits', function (): void {
    config()->set('filesystems.disks.custom', [
        'driver' => 'local',
        'root' => '/tmp',
        'visibility' => 'private',
    ]);

    expect(FileUploadDiskVisibility::resolve('custom', []))->toBe('private');
});

it('falls back to public when disk has no visibility key and root is not laravel stock private/public', function (): void {
    config()->set('filesystems.disks.novis', [
        'driver' => 'local',
        'root' => '/tmp',
    ]);

    expect(FileUploadDiskVisibility::resolve('novis', []))->toBe('public');
});

it('infers private when local disk root is storage/app/private like laravel default local disk', function (): void {
    config()->set('filesystems.disks.privateRoot', [
        'driver' => 'local',
        'root' => storage_path('app/private'),
    ]);

    expect(FileUploadDiskVisibility::resolve('privateRoot', []))->toBe('private');
});

it('infers public when local disk root is storage/app/public like laravel public disk', function (): void {
    config()->set('filesystems.disks.publicRoot', [
        'driver' => 'local',
        'root' => storage_path('app/public'),
    ]);

    expect(FileUploadDiskVisibility::resolve('publicRoot', []))->toBe('public');
});
