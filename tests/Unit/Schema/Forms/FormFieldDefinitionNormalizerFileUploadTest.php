<?php

declare(strict_types=1);

use Flatpack\Schema\Forms\Normalization\FormFieldDefinitionNormalizer;

describe('FormFieldDefinitionNormalizer file-upload relation callback', function () {
    it('omits relation file-upload when callback is missing', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'file-upload',
                'label' => 'Gallery',
                'mode' => 'relation',
                'relation' => 'files',
            ],
            'gallery',
            null,
        );

        expect($out)->toBeNull();
    });

    it('keeps relation file-upload when callback is set', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'file-upload',
                'label' => 'Gallery',
                'mode' => 'relation',
                'relation' => 'files',
                'callback' => 'processGalleryFiles',
            ],
            'gallery',
            null,
        );

        expect($out)->not->toBeNull()
            ->and($out['callback'])->toBe('processGalleryFiles');
    });

    it('preserves image mode and sets target_column when omitted', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'file-upload',
                'label' => 'Photo',
                'mode' => 'image',
            ],
            'photo',
            null,
        );

        expect($out)->not->toBeNull()
            ->and($out['mode'])->toBe('image')
            ->and($out['target_column'])->toBe('photo');
    });

    it('preserves file mode and sets target_column when omitted', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'file-upload',
                'label' => 'Document',
                'mode' => 'file',
            ],
            'document',
            null,
        );

        expect($out)->not->toBeNull()
            ->and($out['mode'])->toBe('file')
            ->and($out['target_column'])->toBe('document');
    });

    it('defaults target_column from field label in url mode when omitted', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'file-upload',
                'label' => 'Cover image',
                'mode' => 'url',
            ],
            'cover_image',
            null,
        );

        expect($out)->not->toBeNull()
            ->and($out['target_column'])->toBe('cover_image');
    });

    it('coerces invalid mode to url', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'file-upload',
                'label' => 'Doc',
                'mode' => 'invalid',
            ],
            'doc',
            null,
        );

        expect($out)->not->toBeNull()
            ->and($out['mode'])->toBe('url');
    });
});
