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
});
