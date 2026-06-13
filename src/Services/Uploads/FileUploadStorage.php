<?php

declare(strict_types=1);

namespace Flatpack\Services\Uploads;

use Illuminate\Http\UploadedFile;

final readonly class FileUploadStorage
{
    public function __construct(
        private FileUploadBrowserUrl $fileUploadBrowserUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>
     */
    public function storeFile(array $fieldDefinition, UploadedFile $file): array
    {
        $disk = FileUploadFieldDisk::resolve(
            isset($fieldDefinition['disk']) ? (string) $fieldDefinition['disk'] : null,
        );

        $directory = trim((string) ($fieldDefinition['directory'] ?? ''));
        $visibility = FileUploadDiskVisibility::resolve($disk, $fieldDefinition);

        $path = $directory !== ''
            ? $file->store($directory, ['disk' => $disk, 'visibility' => $visibility])
            : $file->store('', ['disk' => $disk, 'visibility' => $visibility]);
        $url = $this->fileUploadBrowserUrl->urlForStoredUploadMetadata($fieldDefinition, $disk, $path);

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
