<?php

declare(strict_types=1);

namespace Flatpack\Services\Uploads;

/**
 * Resolves Flysystem visibility for file-upload storage and URL generation.
 *
 * Precedence: field YAML {@code visibility} → disk {@code config/filesystems.php}
 * {@code disks.*.visibility} → for {@code local} driver, infer from {@code root}
 * (Laravel’s default {@code local} disk points at {@code storage/app/private} without a
 * {@code visibility} key) → {@code public} when still unknown.
 */
final class FileUploadDiskVisibility
{
    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    public static function resolve(string $disk, array $fieldDefinition): string
    {
        $disk = trim($disk);
        $fromField = trim((string) ($fieldDefinition['visibility'] ?? ''));
        if ($fromField !== '') {
            return $fromField;
        }

        if ($disk !== '') {
            $fromDiskConfig = self::fromFilesystemsDisk($disk);
            if ($fromDiskConfig !== '') {
                return $fromDiskConfig;
            }

            $fromRoot = self::inferredVisibilityFromLocalDiskRoot($disk);
            if ($fromRoot !== '') {
                return $fromRoot;
            }
        }

        return 'public';
    }

    private static function fromFilesystemsDisk(string $disk): string
    {
        $raw = config('filesystems.disks.' . $disk . '.visibility');
        if (! is_string($raw)) {
            return '';
        }

        $v = trim($raw);

        return $v !== '' ? $v : '';
    }

    /**
     * Laravel’s stock {@code local} disk uses {@code storage_path('app/private')} and often omits
     * {@code visibility}; without this, URL generation incorrectly uses {@code /storage/...}.
     */
    private static function inferredVisibilityFromLocalDiskRoot(string $disk): string
    {
        $driver = config('filesystems.disks.' . $disk . '.driver');
        if ($driver !== 'local') {
            return '';
        }

        $root = config('filesystems.disks.' . $disk . '.root');
        if (! is_string($root) || $root === '') {
            return '';
        }

        $normalized = self::normalizeFilesystemRoot($root);
        if ($normalized === self::normalizeFilesystemRoot(storage_path('app/private'))) {
            return 'private';
        }

        if ($normalized === self::normalizeFilesystemRoot(storage_path('app/public'))) {
            return 'public';
        }

        return '';
    }

    private static function normalizeFilesystemRoot(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
