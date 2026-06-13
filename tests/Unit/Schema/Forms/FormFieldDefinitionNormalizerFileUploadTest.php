<?php

declare(strict_types=1);

use Flatpack\Schema\Forms\Normalization\FormFieldDefinitionNormalizer;

describe('FormFieldDefinitionNormalizer file-upload relation callback', function () {
    it('omits file-upload field when upload block is missing', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'file-upload',
                'label' => 'Attachment',
                'mode' => 'url',
            ],
            'attachment',
            null,
        );

        expect($out)->toBeNull();
    });

    it('omits relation file-upload when callback is missing', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'file-upload',
                'label' => 'Gallery',
                'mode' => 'relation',
                'upload' => [],
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
                'upload' => [],
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
                'upload' => [],
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
                'upload' => [],
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
                'upload' => [],
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
                'upload' => [],
            ],
            'doc',
            null,
        );

        expect($out)->not->toBeNull()
            ->and($out['mode'])->toBe('url');
    });

    it('normalizes rich-text upload config and strips unsupported keys', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'rich-text',
                'label' => 'Body',
                'upload' => [
                    'multiple' => true,
                    'max_files' => '4',
                    'max_size_kb' => '2048',
                    'accept' => [' image/* ', ''],
                    'directory' => ' media/posts ',
                    'disk' => ' media ',
                    'visibility' => 'private',
                    'collection' => ' body ',
                    'relation' => 'files',
                    'callback' => 'saveFiles',
                    'target_column' => 'ignored',
                ],
            ],
            'body',
            null,
        );

        expect($out)->not->toBeNull()
            ->and($out['upload'])->toMatchArray([
                'multiple' => true,
                'max_files' => 4,
                'max_size_kb' => 2048,
                'accept' => ['image/*'],
                'directory' => 'media/posts',
                'disk' => 'media',
                'visibility' => 'private',
                'collection' => 'body',
            ])
            ->and($out['upload'])->not->toHaveKeys([
                'relation',
                'callback',
                'target_column',
                'persist_as',
                'mode',
            ]);
    });

    it('removes invalid editor upload config', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'block-editor',
                'label' => 'Content',
                'upload' => 'invalid',
            ],
            'content',
            null,
        );

        expect($out)->not->toBeNull()
            ->and($out)->not->toHaveKey('upload');
    });
});
