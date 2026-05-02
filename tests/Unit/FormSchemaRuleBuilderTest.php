<?php

declare(strict_types=1);

use Flatpack\Schema\Validation\FormSchemaRuleBuilder;

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
