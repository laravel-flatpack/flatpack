<?php

declare(strict_types=1);

use Flatpack\Services\SaveRecord\ResolveFormPayload;
use Flatpack\Tests\Models\Post;

it('maps url-mode file-upload to target column attribute', function (): void {
    $resolver = new ResolveFormPayload;
    $post = new Post;

    $result = $resolver->validate(
        model: $post,
        compositionType: 'form',
        schema: [
            'fields' => [
                'avatar' => [
                    'type' => 'file-upload',
                    'mode' => 'url',
                    'upload' => [],
                    'target_column' => 'picture',
                    'label' => 'Avatar',
                ],
            ],
        ],
        values: [
            'avatar' => [
                'path' => 'users/avatar.png',
                'url' => '/storage/users/avatar.png',
            ],
        ],
    );

    expect($result->attributes)->toBe([
        'picture' => 'users/avatar.png',
    ]);
    expect($result->hasDeferredRelationPayload)->toBeFalse();
});

it('maps image-mode file-upload to target column like url mode', function (): void {
    $resolver = new ResolveFormPayload;
    $post = new Post;

    $result = $resolver->validate(
        model: $post,
        compositionType: 'form',
        schema: [
            'fields' => [
                'cover' => [
                    'type' => 'file-upload',
                    'mode' => 'image',
                    'upload' => [],
                    'target_column' => 'picture',
                    'label' => 'Cover',
                ],
            ],
        ],
        values: [
            'cover' => [
                'path' => 'uploads/cover.png',
                'url' => '/storage/uploads/cover.png',
            ],
        ],
    );

    expect($result->attributes)->toBe([
        'picture' => 'uploads/cover.png',
    ]);
    expect($result->hasDeferredRelationPayload)->toBeFalse();
});

it('maps file-mode file-upload to target column like url mode', function (): void {
    $resolver = new ResolveFormPayload;
    $post = new Post;

    $result = $resolver->validate(
        model: $post,
        compositionType: 'form',
        schema: [
            'fields' => [
                'attachment' => [
                    'type' => 'file-upload',
                    'mode' => 'file',
                    'upload' => [],
                    'target_column' => 'picture',
                    'label' => 'Attachment',
                ],
            ],
        ],
        values: [
            'attachment' => [
                'path' => 'uploads/terms.pdf',
                'url' => '/storage/uploads/terms.pdf',
            ],
        ],
    );

    expect($result->attributes)->toBe([
        'picture' => 'uploads/terms.pdf',
    ]);
    expect($result->hasDeferredRelationPayload)->toBeFalse();
});

it('marks relation-mode file-upload as deferred relation payload', function (): void {
    $resolver = new ResolveFormPayload;
    $post = new Post;

    $result = $resolver->validate(
        model: $post,
        compositionType: 'form',
        schema: [
            'fields' => [
                'gallery' => [
                    'type' => 'file-upload',
                    'mode' => 'relation',
                    'upload' => ['multiple' => true],
                    'relation' => 'comments',
                    'callback' => 'processGalleryFiles',
                    'label' => 'Gallery',
                    'multiple' => true,
                ],
            ],
        ],
        values: [
            'gallery' => [
                [
                    'path' => 'users/1/a.png',
                    'url' => '/storage/users/1/a.png',
                ],
            ],
        ],
    );

    expect($result->attributes)->toBe([]);
    expect($result->hasDeferredRelationPayload)->toBeTrue();
});
