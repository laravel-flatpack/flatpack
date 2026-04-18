<?php

declare(strict_types=1);

use Flatpack\Support\FormSchemaNormalizer;

it('keeps preset on text when source field exists', function (): void {
    $normalizer = new FormSchemaNormalizer;
    $schema = $normalizer->normalizedFormSchema([
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Title'],
            'slug' => [
                'type' => 'text',
                'label' => 'Slug',
                'preset' => ['field' => 'title', 'type' => 'slug'],
            ],
        ],
    ]);

    expect($schema['fields']['slug']['preset'])->toBe([
        'field' => 'title',
        'type' => 'slug',
    ]);
});

it('removes preset when source field is missing', function (): void {
    $normalizer = new FormSchemaNormalizer;
    $schema = $normalizer->normalizedFormSchema([
        'fields' => [
            'slug' => [
                'type' => 'text',
                'label' => 'Slug',
                'preset' => ['field' => 'missing', 'type' => 'slug'],
            ],
        ],
    ]);

    expect($schema['fields']['slug']['preset'] ?? null)->toBeNull();
});

it('removes preset on self-reference', function (): void {
    $normalizer = new FormSchemaNormalizer;
    $schema = $normalizer->normalizedFormSchema([
        'fields' => [
            'slug' => [
                'type' => 'text',
                'label' => 'Slug',
                'preset' => ['field' => 'slug', 'type' => 'slug'],
            ],
        ],
    ]);

    expect($schema['fields']['slug']['preset'] ?? null)->toBeNull();
});

it('removes preset on field types other than text or textarea', function (): void {
    $normalizer = new FormSchemaNormalizer;
    $schema = $normalizer->normalizedFormSchema([
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Title'],
            'slug' => [
                'type' => 'select',
                'label' => 'Slug',
                'options' => [['value' => 'a', 'label' => 'A']],
                'preset' => ['field' => 'title', 'type' => 'slug'],
            ],
        ],
    ]);

    expect($schema['fields']['slug']['preset'] ?? null)->toBeNull();
});

it('resolves preset source by explicit id over yaml key', function (): void {
    $normalizer = new FormSchemaNormalizer;
    $schema = $normalizer->normalizedFormSchema([
        'fields' => [
            't' => ['id' => 'title', 'type' => 'text', 'label' => 'Title'],
            'slug' => [
                'type' => 'text',
                'label' => 'Slug',
                'preset' => ['field' => 'title', 'type' => 'url'],
            ],
        ],
    ]);

    expect($schema['fields']['slug']['preset'])->toBe([
        'field' => 'title',
        'type' => 'url',
    ]);
});

it('keeps preset on textarea', function (): void {
    $normalizer = new FormSchemaNormalizer;
    $schema = $normalizer->normalizedFormSchema([
        'fields' => [
            'body' => ['type' => 'textarea', 'label' => 'Body'],
            'excerpt' => [
                'type' => 'textarea',
                'label' => 'Excerpt',
                'preset' => ['field' => 'body', 'type' => 'exact'],
            ],
        ],
    ]);

    expect($schema['fields']['excerpt']['preset'])->toBe([
        'field' => 'body',
        'type' => 'exact',
    ]);
});
