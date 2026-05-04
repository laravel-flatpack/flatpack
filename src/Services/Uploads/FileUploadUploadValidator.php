<?php

declare(strict_types=1);

namespace Flatpack\Services\Uploads;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Validates {@code files[]} for {@see FileUploadController} using YAML field definition
 * merged with {@code config('flatpack.uploads.*')} ceilings.
 */
final readonly class FileUploadUploadValidator
{
    /** @var array<string, string> */
    private const array PRIMARY_MIME_BY_EXTENSION = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'json' => 'application/json',
        'zip' => 'application/zip',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * @param  list<UploadedFile|mixed>  $files
     *
     * @throws ValidationException
     */
    public function validate(array $fieldDefinition, array $files): void
    {
        $uploaded = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploaded[] = $file;
            }
        }

        $maxFiles = $this->effectiveMaxFilesPerRequest($fieldDefinition);
        if (count($uploaded) > $maxFiles) {
            throw ValidationException::withMessages([
                'files' => sprintf('You may upload at most %d file(s).', $maxFiles),
            ]);
        }

        $maxKb = $this->effectiveMaxSizeKb($fieldDefinition);
        $acceptRule = $this->acceptClosureRule($fieldDefinition);

        foreach ($uploaded as $index => $file) {
            $rules = ['required', 'file', 'max:' . $maxKb];
            if ($acceptRule !== null) {
                $rules[] = $acceptRule;
            }

            $validator = Validator::make(
                ['file' => $file],
                ['file' => $rules],
                [],
                ['file' => 'files.' . $index],
            );

            if ($validator->fails()) {
                throw ValidationException::withMessages(
                    $this->remapFileErrorsToFilesKey($validator->errors()->getMessages()),
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    public function effectiveMaxSizeKb(array $fieldDefinition): int
    {
        $configCeiling = $this->positiveIntOr(config('flatpack.uploads.max_size_kb'), 10240);
        $fromField = $this->optionalPositiveInt($fieldDefinition['max_size_kb'] ?? null);
        $base = $fromField ?? $configCeiling;

        return min($base, $configCeiling);
    }

    /**
     * Max files accepted in a single upload request for this field.
     *
     * @param  array<string, mixed>  $fieldDefinition
     */
    public function effectiveMaxFilesPerRequest(array $fieldDefinition): int
    {
        $configCeiling = $this->positiveIntOr(config('flatpack.uploads.max_files'), 10);
        $multiple = ($fieldDefinition['multiple'] ?? false) === true;

        if (! $multiple) {
            return min(1, $configCeiling);
        }

        $fromField = $this->optionalPositiveInt($fieldDefinition['max_files'] ?? null);
        $base = $fromField ?? $configCeiling;

        return min($base, $configCeiling);
    }

    /**
     * @return list<string>
     */
    private static function imageWildcardMimes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/bmp',
            'image/x-ms-bmp',
            'image/avif',
            'image/heic',
            'image/heif',
            'image/tiff',
            'image/x-icon',
            'image/vnd.microsoft.icon',
        ];
    }

    /**
     * @return list<string>
     */
    private static function audioWildcardMimes(): array
    {
        return [
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/x-wav',
            'audio/ogg',
            'audio/webm',
            'audio/aac',
            'audio/flac',
            'audio/x-m4a',
            'audio/mp4',
            'audio/m4a',
        ];
    }

    /**
     * @return list<string>
     */
    private static function videoWildcardMimes(): array
    {
        return [
            'video/mp4',
            'video/webm',
            'video/ogg',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-ms-wmv',
            'video/mpeg',
        ];
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function acceptClosureRule(array $fieldDefinition): ?Closure
    {
        $tokens = $this->normalizeAcceptTokens($fieldDefinition['accept'] ?? null);
        if ($tokens === []) {
            return null;
        }

        $allowedMimes = [];
        $allowedExtensions = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            if (str_contains($token, '/')) {
                foreach ($this->expandAcceptMimeToken($token) as $mime) {
                    $mime = mb_strtolower(trim($mime));
                    if ($mime !== '' && ! str_contains($mime, '*')) {
                        $allowedMimes[] = $mime;
                    }
                }

                continue;
            }

            $ext = mb_strtolower(ltrim($token, '.'));
            if ($ext !== '') {
                $allowedExtensions[] = $ext;
                if (isset(self::PRIMARY_MIME_BY_EXTENSION[$ext])) {
                    $allowedMimes[] = self::PRIMARY_MIME_BY_EXTENSION[$ext];
                }
            }
        }

        $allowedMimes = array_values(array_unique($allowedMimes));
        $allowedExtensions = array_values(array_unique($allowedExtensions));

        if ($allowedMimes === [] && $allowedExtensions === []) {
            return null;
        }

        return function (string $attribute, mixed $value, Closure $fail) use ($allowedMimes, $allowedExtensions): void {
            if (! $value instanceof UploadedFile) {
                return;
            }

            $mime = mb_strtolower((string) $value->getMimeType());
            $ext = mb_strtolower((string) $value->getClientOriginalExtension());

            if ($allowedMimes !== [] && in_array($mime, $allowedMimes, true)) {
                return;
            }

            if ($allowedExtensions !== [] && in_array($ext, $allowedExtensions, true)) {
                return;
            }

            $fail('The file type is not allowed for this field.');
        };
    }

    /**
     * @return list<string>
     */
    private function normalizeAcceptTokens(mixed $accept): array
    {
        if (is_string($accept)) {
            $trimmed = trim($accept);

            return $trimmed === '' ? [] : [$trimmed];
        }

        if (! is_array($accept)) {
            return [];
        }

        $out = [];
        foreach ($accept as $item) {
            if (is_string($item)) {
                $t = trim($item);
                if ($t !== '') {
                    $out[] = $t;
                }
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function expandAcceptMimeToken(string $token): array
    {
        $lower = mb_strtolower($token);

        return match ($lower) {
            'image/*' => self::imageWildcardMimes(),
            'audio/*' => self::audioWildcardMimes(),
            'video/*' => self::videoWildcardMimes(),
            default => [$token],
        };
    }

    /**
     * @param  array<string, array<int, string>>  $messages
     * @return array<string, array<int, string>>
     */
    private function remapFileErrorsToFilesKey(array $messages): array
    {
        $out = [];
        foreach ($messages as $key => $lines) {
            if (str_starts_with($key, 'file')) {
                $out['files'] = array_merge($out['files'] ?? [], $lines);
            } else {
                $out[$key] = $lines;
            }
        }

        return $out;
    }

    private function optionalPositiveInt(mixed $value): ?int
    {
        if (! is_int($value) && ! is_float($value)) {
            return null;
        }

        $n = (int) $value;

        return $n >= 1 ? $n : null;
    }

    private function positiveIntOr(mixed $value, int $fallback): int
    {
        $n = $this->optionalPositiveInt($value);

        return $n ?? $fallback;
    }
}
