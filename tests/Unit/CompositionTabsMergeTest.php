<?php

declare(strict_types=1);

use Flatpack\Schema\CompositionTabsMerge;
use Flatpack\Schema\Validation\FormSchemaRuleBuilder;

it('CompositionTabsMerge::form returns null for null schema', function (): void {
    expect(CompositionTabsMerge::form(null))->toBeNull();
});

it('CompositionTabsMerge::list returns null for null schema', function (): void {
    expect(CompositionTabsMerge::list(null))->toBeNull();
});

it('CompositionTabsMerge::form returns same schema when tabs are absent', function (): void {
    $schema = ['fields' => ['title' => ['type' => 'text', 'label' => 'Title']]];
    expect(CompositionTabsMerge::form($schema))->toBe($schema);
});

it('CompositionTabsMerge::form returns same schema when tabs are an empty array', function (): void {
    $schema = ['tabs' => [], 'fields' => []];
    expect(CompositionTabsMerge::form($schema))->toBe($schema);
});

it('CompositionTabsMerge::list returns same schema when tabs are absent', function (): void {
    $schema = ['columns' => [['id' => 'title', 'type' => 'text', 'label' => 'Title']]];
    expect(CompositionTabsMerge::list($schema))->toBe($schema);
});

it('CompositionTabsMerge::list returns same schema when tabs are an empty array', function (): void {
    $schema = ['tabs' => [], 'columns' => []];
    expect(CompositionTabsMerge::list($schema))->toBe($schema);
});

it('CompositionTabsMerge::form merges tab fields using the same pipe as normalizers', function (): void {
    $merged = CompositionTabsMerge::form([
        'tabs' => [
            'main' => [
                'label' => 'Main',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Title'],
                ],
            ],
        ],
    ]);

    expect($merged)->toHaveKey('fields')
        ->and($merged)->toHaveKey('tab_panels')
        ->and($merged)->not->toHaveKey('tabs')
        ->and($merged['fields']['title']['label'])->toBe('Title');
});

it('CompositionTabsMerge::list merges tab columns using the same pipe as normalizers', function (): void {
    $merged = CompositionTabsMerge::list([
        'tabs' => [
            'main' => [
                'label' => 'Main',
                'columns' => [
                    [
                        'id' => 'title',
                        'type' => 'text',
                        'label' => 'Title',
                    ],
                ],
            ],
        ],
    ]);

    expect($merged)->toHaveKey('columns')
        ->and($merged)->toHaveKey('tab_panels')
        ->and($merged)->not->toHaveKey('tabs')
        ->and($merged['columns'])->toHaveCount(1)
        ->and($merged['columns'][0]['id'])->toBe('title');
});

it('merges form tabs before building value validation rules', function (): void {
    $schema = [
        'tabs' => [
            'main' => [
                'label' => 'Main',
                'fields' => [
                    'title' => [
                        'type' => 'text',
                        'label' => 'Title',
                        'required' => true,
                    ],
                ],
            ],
        ],
    ];

    $rules = (new FormSchemaRuleBuilder)->rulesForValues(
        $schema,
        'Flatpack\\Tests\\Models\\Post',
    );

    expect($rules)->toHaveKey('values.title');
});
