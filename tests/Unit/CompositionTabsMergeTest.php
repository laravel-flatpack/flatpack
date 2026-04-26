<?php

declare(strict_types=1);

use Flatpack\Schema\Validation\FormSchemaRuleBuilder;

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
