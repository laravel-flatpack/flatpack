<?php

declare(strict_types=1);

use Flatpack\Services\Uploads\FileUploadUploadValidator;
use Flatpack\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'flatpack.uploads.max_size_kb' => 100,
        'flatpack.uploads.max_files' => 5,
    ]);
});

it('effectiveMaxSizeKb uses field value capped by config', function (): void {
    $v = new FileUploadUploadValidator;

    expect($v->effectiveMaxSizeKb(['max_size_kb' => 200]))->toBe(100)
        ->and($v->effectiveMaxSizeKb(['max_size_kb' => 50]))->toBe(50)
        ->and($v->effectiveMaxSizeKb([]))->toBe(100);
});

it('effectiveMaxFilesPerRequest caps by config and forces one when not multiple', function (): void {
    $v = new FileUploadUploadValidator;

    expect($v->effectiveMaxFilesPerRequest(['multiple' => true, 'max_files' => 99]))->toBe(5)
        ->and($v->effectiveMaxFilesPerRequest(['multiple' => true, 'max_files' => 2]))->toBe(2)
        ->and($v->effectiveMaxFilesPerRequest(['multiple' => true]))->toBe(5)
        ->and($v->effectiveMaxFilesPerRequest(['multiple' => false]))->toBe(1);
});

it('rejects files over effective max size', function (): void {
    $v = new FileUploadUploadValidator;
    $big = UploadedFile::fake()->create('huge.pdf', 150, 'application/pdf');

    expect(fn () => $v->validate(['max_size_kb' => 100], [$big]))
        ->toThrow(ValidationException::class);
});

it('rejects too many files for multiple field', function (): void {
    $v = new FileUploadUploadValidator;
    $files = [
        UploadedFile::fake()->create('a.pdf', 1, 'application/pdf'),
        UploadedFile::fake()->create('b.pdf', 1, 'application/pdf'),
        UploadedFile::fake()->create('c.pdf', 1, 'application/pdf'),
    ];

    expect(fn () => $v->validate(['multiple' => true, 'max_files' => 2], $files))
        ->toThrow(ValidationException::class);
});

it('rejects file when accept mimes exclude type', function (): void {
    $v = new FileUploadUploadValidator;
    $png = UploadedFile::fake()->image('x.png');

    expect(fn () => $v->validate(['accept' => ['application/pdf']], [$png]))
        ->toThrow(ValidationException::class);
});

it('allows file matching accept', function (): void {
    $v = new FileUploadUploadValidator;
    $pdf = UploadedFile::fake()->create('x.pdf', 1, 'application/pdf');

    $v->validate(['accept' => ['application/pdf']], [$pdf]);

    expect(true)->toBeTrue();
});
