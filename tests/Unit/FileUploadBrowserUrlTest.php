<?php

declare(strict_types=1);

use Flatpack\Services\Uploads\FileUploadBrowserUrl;
use Flatpack\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

uses(TestCase::class);

test('disk media resolves to media_disk for browse URL generation', function (): void {
    config()->set('flatpack.uploads.media_disk', 'resolved_media');
    Storage::fake('resolved_media');
    Storage::disk('resolved_media')->put('photo.png', 'bin');

    $url = (new FileUploadBrowserUrl)->resolveBrowseUrlForField(
        ['disk' => 'media', 'visibility' => 'private'],
        'photo.png',
    );

    expect($url)->toContain('flatpack');
    $parsed = parse_url($url);
    expect($parsed['query'] ?? '')->toContain('signature')
        ->and($parsed['query'] ?? '')->toContain('disk=resolved_media');
});

test('private file uses temporary signed flatpack serve route when disk has no native getTemporaryUrl', function () {
    $disk = 'local';
    Storage::fake($disk);
    Storage::disk($disk)->put('doc.txt', 'x');

    $url = (new FileUploadBrowserUrl)->resolveBrowseUrlForField(
        ['disk' => $disk, 'visibility' => 'private'],
        'doc.txt',
    );

    expect($url)->toContain('flatpack');
    $parsed = parse_url($url);
    expect($parsed['query'] ?? '')->toContain('signature');
});
