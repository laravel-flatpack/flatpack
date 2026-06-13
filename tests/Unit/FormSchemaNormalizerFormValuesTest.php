<?php

declare(strict_types=1);

use Flatpack\Schema\Forms\FormSchemaNormalizer;
use Flatpack\Tests\Models\Category;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

it('returns empty array when model or schema is missing', function (): void {
    $n = new FormSchemaNormalizer;

    expect($n->formValuesFromModel(null, null))->toBe([]);
});

it('builds values from a model using simple and relation-backed combobox fields', function (): void {
    $category = Category::factory()->createOne(['name' => 'News']);
    /** @var Post $post */
    $post = Post::factory()->createOne(['category_id' => $category->getKey()]);

    $normalizer = new FormSchemaNormalizer;
    $normalized = $normalizer->normalizedFormSchema([
        'fields' => [
            'title' => [
                'type' => 'text',
                'label' => 'Title',
            ],
            'category_id' => [
                'type' => 'combobox',
                'label' => 'Category',
                'relation' => 'category',
                'relation_name' => 'name',
                'relation_value' => 'id',
            ],
        ],
    ], null, Post::class, $post);

    expect($normalized)->not->toBeNull();
    $values = $normalizer->formValuesFromModel($post, $normalized);

    expect($values['title'])->toBe($post->title)
        ->and($values['category_id'])->toBe((string) $category->getKey());
});

it('hydrates image-mode file-upload from target_column like url mode', function (): void {
    /** @var Post $post */
    $post = Post::factory()->createOne([
        'picture' => '/storage/users/hero.jpg',
    ]);

    $normalizer = new FormSchemaNormalizer;
    $normalized = $normalizer->normalizedFormSchema([
        'fields' => [
            'hero' => [
                'type' => 'file-upload',
                'mode' => 'image',
                'upload' => [],
                'target_column' => 'picture',
                'label' => 'Hero',
            ],
        ],
    ], null, Post::class, $post);

    expect($normalized)->not->toBeNull();
    $values = $normalizer->formValuesFromModel($post, $normalized);

    expect($values['hero'])->toBe([
        'url' => '/storage/users/hero.jpg',
    ]);
});

it('hydrates url-mode file-upload from target_column for a single file', function (): void {
    /** @var Post $post */
    $post = Post::factory()->createOne([
        'picture' => '/storage/users/avatar.png',
    ]);

    $normalizer = new FormSchemaNormalizer;
    $normalized = $normalizer->normalizedFormSchema([
        'fields' => [
            'avatar' => [
                'type' => 'file-upload',
                'mode' => 'url',
                'upload' => [],
                'target_column' => 'picture',
                'label' => 'Avatar',
            ],
        ],
    ], null, Post::class, $post);

    expect($normalized)->not->toBeNull();
    $values = $normalizer->formValuesFromModel($post, $normalized);

    expect($values['avatar'])->toBe([
        'url' => '/storage/users/avatar.png',
    ]);
});

it('hydrates url-mode file-upload with persist_as json arrays', function (): void {
    $post = new Post;
    $post->forceFill([
        'picture' => [
            '/storage/users/one.png',
            '/storage/users/two.png',
        ],
    ]);

    $normalizer = new FormSchemaNormalizer;
    $normalized = $normalizer->normalizedFormSchema([
        'fields' => [
            'gallery' => [
                'type' => 'file-upload',
                'mode' => 'url',
                'upload' => ['multiple' => true],
                'target_column' => 'picture',
                'multiple' => true,
                'persist_as' => 'json',
                'label' => 'Gallery',
            ],
        ],
    ], null, Post::class, $post);

    expect($normalized)->not->toBeNull();
    $values = $normalizer->formValuesFromModel($post, $normalized);

    expect($values['gallery'])->toBe([
        ['url' => '/storage/users/one.png'],
        ['url' => '/storage/users/two.png'],
    ]);
});

it('hydrates url-mode file-upload with persist_as string csv values', function (): void {
    /** @var Post $post */
    $post = Post::factory()->createOne([
        'picture' => ' /storage/users/a.png, /storage/users/b.png ,, ',
    ]);

    $normalizer = new FormSchemaNormalizer;
    $normalized = $normalizer->normalizedFormSchema([
        'fields' => [
            'gallery' => [
                'type' => 'file-upload',
                'mode' => 'url',
                'upload' => ['multiple' => true],
                'target_column' => 'picture',
                'multiple' => true,
                'persist_as' => 'string',
                'label' => 'Gallery',
            ],
        ],
    ], null, Post::class, $post);

    expect($normalized)->not->toBeNull();
    $values = $normalizer->formValuesFromModel($post, $normalized);

    expect($values['gallery'])->toBe([
        ['url' => '/storage/users/a.png'],
        ['url' => '/storage/users/b.png'],
    ]);
});
