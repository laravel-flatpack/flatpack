<?php

declare(strict_types=1);

namespace Flatpack\Services\Uploads;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Builds URLs suitable for browser embedding ({@code <img src>}, links) for uploaded files.
 *
 * Public disks may use Flysystem public URLs. When a signed URL is needed, disks whose Flysystem
 * adapter implements {@code getTemporaryUrl} (e.g. S3 presigned URLs) use
 * {@see FilesystemAdapter::temporaryUrl()}; otherwise a temporary signed route to
 * {@code flatpack.uploads.serve} is used (avoiding Laravel {@code Storage::fake()} helpers and
 * optional {@code storage.{disk}} URLs for local disks).
 */
final readonly class FileUploadBrowserUrl
{
    /**
     * Resolve a browse URL for values persisted on the parent model (URL column / CSV / JSON list).
     *
     * @param  array<string, mixed>  $fieldDefinition
     */
    public function resolveBrowseUrlForField(array $fieldDefinition, string $stored): string
    {
        $stored = trim($stored);
        if ($stored === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $stored)) {
            return $stored;
        }

        $disk = $this->diskForUrlModeField($fieldDefinition);
        $path = $this->normalizeStoredToExistingPath($disk, $stored);
        if ($path === null) {
            return $stored;
        }

        $visibility = $this->visibilityForField($fieldDefinition, $disk);

        return $this->publicOrSignedUrl($disk, $path, $visibility);
    }

    /**
     * Browse URL right after {@see FileUploadStorage::storeFile()} using disk + relative path.
     *
     * @param  array<string, mixed>  $fieldDefinition
     */
    public function urlForStoredUploadMetadata(array $fieldDefinition, string $disk, string $path): string
    {
        $disk = trim($disk);
        $path = trim($path);
        if ($disk === '' || $path === '') {
            return '';
        }

        $visibility = $this->visibilityForField($fieldDefinition, $disk);

        return $this->publicOrSignedUrl($disk, $path, $visibility);
    }

    /**
     * Ensures {@code url} on a relation fragment loads in the browser for private disks.
     *
     * @param  array<string, mixed>  $fragment
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>
     */
    public function ensureFragmentBrowseUrl(array $fragment, array $fieldDefinition): array
    {
        $disk = trim((string) ($fragment['disk'] ?? ''));
        $path = trim((string) ($fragment['path'] ?? ''));
        if ($disk === '' || $path === '') {
            return $fragment;
        }

        if (! Storage::disk($disk)->exists($path)) {
            return $fragment;
        }

        $visibility = trim((string) ($fragment['visibility'] ?? ''));
        if ($visibility === '') {
            $visibility = $this->visibilityForField($fieldDefinition, $disk);
        }

        $fragment['url'] = $this->publicOrSignedUrl($disk, $path, $visibility);

        return $fragment;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function diskForUrlModeField(array $fieldDefinition): string
    {
        return FileUploadFieldDisk::resolve(
            isset($fieldDefinition['disk']) ? (string) $fieldDefinition['disk'] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function visibilityForField(array $fieldDefinition, string $disk): string
    {
        return FileUploadDiskVisibility::resolve($disk, $fieldDefinition);
    }

    private function publicOrSignedUrl(string $disk, string $path, string $visibility): string
    {
        $driverUrl = trim((string) Storage::disk($disk)->url($path));
        $forceSigned = $visibility === 'private';
        if (! $forceSigned && $driverUrl !== '') {
            return $driverUrl;
        }

        $expiration = now()->addMinutes($this->signedTtlMinutes());

        $filesystem = Storage::disk($disk);
        if (
            $filesystem instanceof FilesystemAdapter
            && method_exists($filesystem->getAdapter(), 'getTemporaryUrl')
        ) {
            return $filesystem->temporaryUrl($path, $expiration);
        }

        return URL::temporarySignedRoute(
            'flatpack.uploads.serve',
            $expiration,
            ['disk' => $disk, 'path' => $path],
        );
    }

    private function signedTtlMinutes(): int
    {
        $ttl = (int) config('flatpack.uploads.signed_url_ttl_minutes', 10_080);

        return max(1, $ttl);
    }

    private function normalizeStoredToExistingPath(string $disk, string $stored): ?string
    {
        $candidates = array_unique(array_filter([
            $stored,
            ltrim($stored, '/'),
            basename($stored),
        ], static fn (string $s): bool => $s !== ''));

        foreach ($candidates as $candidate) {
            if (str_contains($candidate, '..')) {
                continue;
            }
            if (Storage::disk($disk)->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
