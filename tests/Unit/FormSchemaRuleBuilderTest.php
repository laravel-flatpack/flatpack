<?php

declare(strict_types=1);

use Flatpack\Schema\Validation\FormSchemaRuleBuilder;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

it('returns empty rules when schema is null or fields are missing', function (): void {
    $b = new FormSchemaRuleBuilder;

    expect($b->rulesForValues(null, Post::class))->toBe([])
        ->and($b->rulesForValues([], Post::class))->toBe([])
        ->and($b->rulesForValues(['fields' => 'bad'], Post::class))->toBe([]);
});

it('skips non-array field definitions and blank ids', function (): void {
    $b = new FormSchemaRuleBuilder;
    $rules = $b->rulesForValues([
        'fields' => [
            'bad' => 'x',
            'empty' => ['type' => 'text', 'id' => '  '],
            'ok' => ['type' => 'text', 'label' => 'Title'],
        ],
    ], Post::class);

    expect($rules)->toHaveKey('values.ok')->not->toHaveKey('values.bad')->not->toHaveKey('values.empty');
});

it('skips toolbar and widget fields', function (): void {
    $b = new FormSchemaRuleBuilder;
    $rules = $b->rulesForValues([
        'fields' => [
            'tbar' => ['type' => 'toolbar', 'actions' => ['save' => []]],
            'w' => ['type' => 'widget', 'widget' => ['m' => ['type' => 'metric']]],
            'title' => ['type' => 'text', 'label' => 'T'],
        ],
    ], Post::class);

    expect($rules)->toHaveKey('values.title')->not->toHaveKeys(['values.tbar', 'values.w']);
});

it('merges passthrough rules from the rules key', function (): void {
    $b = new FormSchemaRuleBuilder;
    $rules = $b->rulesForValues([
        'fields' => [
            'slug' => [
                'type' => 'text',
                'label' => 'Slug',
                'rules' => 'max:120',
            ],
        ],
    ], Post::class);

    expect($rules['values.slug'])->toContain('max:120');
});

it('uses relation exists rule for single-value relation combobox', function (): void {
    $b = new FormSchemaRuleBuilder;
    $rules = $b->rulesForValues([
        'fields' => [
            'category_id' => [
                'type' => 'combobox',
                'label' => 'Category',
                'relation' => 'category',
                'relation_name' => 'name',
                'relation_value' => 'id',
            ],
        ],
    ], Post::class);

    $stringRules = array_values(array_filter(
        $rules['values.category_id'] ?? [],
        static fn (mixed $r): bool => is_string($r),
    ));
    expect(implode('|', $stringRules))->toContain('exists:');
});

it('uses array rule for deferred relation sync fields', function (): void {
    $b = new FormSchemaRuleBuilder;
    $rules = $b->rulesForValues([
        'fields' => [
            'lines' => [
                'type' => 'table',
                'label' => 'Lines',
                'relation' => 'lines',
                'columns' => [
                    ['id' => 'sku', 'label' => 'SKU'],
                ],
            ],
        ],
    ], Post::class);

    expect($rules['values.lines'])->toContain('array');
});

it('uses select In rule when options are present', function (): void {
    $b = new FormSchemaRuleBuilder;
    $rules = $b->rulesForValues([
        'fields' => [
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    ['value' => 'a', 'label' => 'A'],
                    ['value' => 'b', 'label' => 'B'],
                ],
            ],
        ],
    ], Post::class);

    expect($rules['values.status'][2] ?? null)->not->toBe('string');
});

it('uses string rule for combobox without relation metadata', function (): void {
    $b = new FormSchemaRuleBuilder;
    $rules = $b->rulesForValues([
        'fields' => [
            'tag' => [
                'type' => 'combobox',
                'label' => 'Tag',
            ],
        ],
    ], Post::class);

    expect($rules['values.tag'])->toContain('string');
});

it('uses array rule for multi combobox', function (): void {
    $b = new FormSchemaRuleBuilder;
    $rules = $b->rulesForValues([
        'fields' => [
            'tags' => [
                'type' => 'combobox',
                'label' => 'Tags',
                'multiple' => true,
                'relation' => 'tags',
            ],
        ],
    ], Post::class);

    expect($rules['values.tags'])->toContain('array');
});

it('uses array rule for file-upload fields', function (): void {
    $b = new FormSchemaRuleBuilder;
    $rules = $b->rulesForValues([
        'fields' => [
            'cover' => [
                'type' => 'file-upload',
                'label' => 'Cover',
                'mode' => 'url',
                'upload' => [],
            ],
        ],
    ], Post::class);

    expect($rules['values.cover'])->toContain('array');
});

it('maps checkbox and date-picker types', function (): void {
    $b = new FormSchemaRuleBuilder;
    $rules = $b->rulesForValues([
        'fields' => [
            'active' => ['type' => 'checkbox', 'label' => 'Active'],
            'published' => ['type' => 'date-picker', 'label' => 'Pub'],
        ],
    ], Post::class);

    expect($rules['values.active'])->toContain('boolean')
        ->and($rules['values.published'])->toContain('date');
});

it('uses array validation for repeater fields', function (): void {
    $schema = [
        'fields' => [
            'meta_data' => [
                'type' => 'repeater',
                'label' => 'Meta',
                'form' => [
                    'fields' => [
                        'meta-title' => [
                            'type' => 'text',
                            'label' => 'Title',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $rules = (new FormSchemaRuleBuilder)->rulesForValues(
        $schema,
        'Flatpack\\Tests\\Models\\Post',
    );

    expect($rules)->toHaveKey('values.meta_data');
    expect($rules['values.meta_data'])->toContain('nullable');
    expect($rules['values.meta_data'])->toContain('array');
    expect($rules['values.meta_data'])->not->toContain('string');
});

it('uses array validation for plate editor fields', function (): void {
    $schema = [
        'fields' => [
            'body_rich' => [
                'type' => 'rich-text',
                'label' => 'Body',
            ],
            'body_blocks' => [
                'type' => 'block-editor',
                'label' => 'Blocks',
            ],
        ],
    ];

    $rules = (new FormSchemaRuleBuilder)->rulesForValues(
        $schema,
        'Flatpack\\Tests\\Models\\Post',
    );

    expect($rules['values.body_rich'])->toContain('array')->not->toContain('string');
    expect($rules['values.body_blocks'])->toContain('array')->not->toContain('string');
});
