<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves files from non-public disks (e.g. {@code storage/app/private}) for authenticated users.
 *
 * URL is produced by {@see \Flatpack\Services\Uploads\FileUploadBrowserUrl} as a temporary signed link.
 */
final readonly class FileUploadServeController
{
    public function __invoke(Request $request): StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $disk = trim((string) $request->query('disk', ''));
        $path = trim((string) $request->query('path', ''));
        if ($disk === '' || $path === '' || str_contains($path, '..')) {
            abort(404);
        }

        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        return Storage::disk($disk)->response($path, null, [
            'Content-Disposition' => 'inline',
        ]);
    }
}
