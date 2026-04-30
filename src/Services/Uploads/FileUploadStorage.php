<?php

declare(strict_types=1);

namespace Flatpack\Services\Uploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final readonly class FileUploadStorage
{
    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>
     */
    public function storeFile(array $fieldDefinition, UploadedFile $file): array
    {
        $mode = trim((string) ($fieldDefinition['mode'] ?? 'url'));
        $defaultDisk = $mode === 'relation'
            ? (string) config('flatpack.uploads.media_disk', 'public')
            : (string) config('flatpack.uploads.file_disk', 'public');
        $disk = trim((string) ($fieldDefinition['disk'] ?? $defaultDisk));
        if ($disk === '') {
            $disk = $defaultDisk;
        }

        $directory = trim((string) ($fieldDefinition['directory'] ?? ''));
        $visibility = trim((string) ($fieldDefinition['visibility'] ?? (string) config('flatpack.uploads.visibility', 'public')));

        $path = $directory !== ''
            ? $file->store($directory, ['disk' => $disk, 'visibility' => $visibility])
            : $file->store('', ['disk' => $disk, 'visibility' => $visibility]);
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk($disk);
        $url = trim((string) $storage->url($path));

        return [
            'disk' => $disk,
            'path' => $path,
            'url' => $url !== '' ? $url : $path,
            'name' => $file->getClientOriginalName(),
            'mime_type' => (string) $file->getClientMimeType(),
            'size' => is_int($file->getSize()) ? $file->getSize() : 0,
            'visibility' => $visibility !== '' ? $visibility : null,
        ];
    }
}
