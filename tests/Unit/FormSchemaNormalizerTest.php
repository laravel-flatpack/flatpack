<?php

declare(strict_types=1);

use Flatpack\Support\CompositionDebugLog;
use Flatpack\Support\FormSchemaNormalizer;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

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

it('omits fields with unknown types and warns in debug log', function (): void {
    $log = new CompositionDebugLog('posts/form.yaml');
    $normalizer = new FormSchemaNormalizer;
    $schema = $normalizer->normalizedFormSchema([
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Title'],
            'wrong_field' => [
                'type' => 'gsgsf',
                'label' => 'Should be skipped in the form',
            ],
        ],
    ], $log);

    expect($schema['fields'])->not->toHaveKey('wrong_field');
    expect($schema['fields']['title'])->toBeArray();

    $joined = implode(' ', $log->all());
    expect($joined)->toContain('wrong_field');
    expect($joined)->toContain('gsgsf');
    expect($joined)->toContain('unknown type');
});

it('omits fields with unknown types when app debug is off', function (): void {
    $normalizer = new FormSchemaNormalizer;
    $schema = $normalizer->normalizedFormSchema([
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Title'],
            'wrong_field' => [
                'type' => 'nope',
                'label' => 'Bad',
            ],
        ],
    ]);

    expect($schema['fields'])->not->toHaveKey('wrong_field');
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

it('appends debug messages when debug log is provided', function (): void {
    $log = new CompositionDebugLog('posts/form.yaml');
    $normalizer = new FormSchemaNormalizer;
    $normalizer->normalizedFormSchema([
        'fields' => [
            'slug' => [
                'type' => 'text',
                'label' => 'Slug',
                'preset' => ['field' => 'missing', 'type' => 'slug'],
            ],
        ],
    ], $log);

    expect($log->all())->toHaveCount(1);
    expect($log->all()[0])->toContain('slug');
    expect($log->all()[0])->toContain('does not exist');
});

it('records unknown keys under actions in debug log', function (): void {
    $log = new CompositionDebugLog('posts/form.yaml');
    $normalizer = new FormSchemaNormalizer;
    $normalizer->normalizedFormSchema([
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Title'],
        ],
        'actions' => [
            'publish' => [
                'labelllllll' => 'Publish',
                'actionnnnnn' => 'publish',
                'variant' => 'primary',
                'icon' => 'check',
            ],
        ],
    ], $log);

    $joined = implode(' ', $log->all());
    expect($joined)->toContain('labelllllll');
    expect($joined)->toContain('actionnnnnn');
    expect($joined)->toContain('actions.publish');
});

it('records unknown top-level form keys in debug log', function (): void {
    $log = new CompositionDebugLog('posts/form.yaml');
    $normalizer = new FormSchemaNormalizer;
    $normalizer->normalizedFormSchema([
        'name' => 'Post',
        'asdasdasd' => 'asd ad asd a',
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Title'],
        ],
    ], $log);

    expect($log->all())->not->toBeEmpty();
    expect(implode(' ', $log->all()))->toContain('asdasdasd');
    expect(implode(' ', $log->all()))->toContain('Unknown top-level form key');
});

it('records wrong-type preset removal in debug log', function (): void {
    $log = new CompositionDebugLog('posts/form.yaml');
    $normalizer = new FormSchemaNormalizer;
    $normalizer->normalizedFormSchema([
        'fields' => [
            'slug' => [
                'type' => 'select',
                'label' => 'Slug',
                'options' => [['value' => 'a', 'label' => 'A']],
                'preset' => ['field' => 'title', 'type' => 'slug'],
            ],
        ],
    ], $log);

    expect($log->all())->toHaveCount(1);
    expect($log->all()[0])->toContain('only text and textarea');
});
