<?php

declare(strict_types=1);

namespace Flatpack\Services\Uploads;

/**
 * Resolves YAML {@code disk} for {@code type: file-upload} fields to a Laravel filesystem disk name.
 *
 * Rules: omitted disk → {@code flatpack.uploads.file_disk}; literal {@code media} →
 * {@code flatpack.uploads.media_disk}; any other non-empty string → used as the disk name.
 */
final class FileUploadFieldDisk
{
    public static function resolve(?string $yamlDisk): string
    {
        $trimmed = trim((string) ($yamlDisk ?? ''));
        if ($trimmed === '') {
            return (string) config('flatpack.uploads.file_disk', 'local');
        }

        if ($trimmed === 'media') {
            return (string) config('flatpack.uploads.media_disk', 'local');
        }

        return $trimmed;
    }
}
