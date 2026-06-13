<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms\Normalization;

use Illuminate\Database\Eloquent\Model;

/**
 * Builds file-upload field values from related models for form hydration.
 *
 * Each related model should expose attributes whose names match upload metadata
 * keys ({@see self::METADATA_KEYS}). Use accessors when DB columns differ
 * (e.g. {@code getNameAttribute} for {@code original_name}).
 */
final class FileUploadRelationHydrator
{
    /** @var list<string> */
    public const array METADATA_KEYS = [
        'disk',
        'path',
        'url',
        'name',
        'mime_type',
        'size',
        'visibility',
        'collection',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function fragmentFromRelatedModel(Model $model): array
    {
        $out = [];
        foreach (self::METADATA_KEYS as $key) {
            $value = $model->getAttribute($key);
            if ($value === null || $value === '') {
                continue;
            }

            $out[$key] = $value;
        }

        return $out;
    }
}
