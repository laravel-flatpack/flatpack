<?php

declare(strict_types=1);

use Flatpack\Services\SaveRecord\DeferredRelationValidationService;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

test('deferred relation validator reports required errors for all invalid rows', function () {
    $service = new DeferredRelationValidationService();

    $schema = [
        'fields' => [
            'comments' => [
                'type' => 'table',
                'label' => 'Comments',
                'relation' => 'comments',
                'columns' => [
                    'content' => [
                        'label' => 'Content',
                        'type' => 'text',
                    ],
                    'user_id' => [
                        'label' => 'User',
                        'type' => 'relation',
                        'edit_form_field' => [
                            'type' => 'combobox',
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ],
    ];

    $errors = $service->requiredRowErrors($schema, [
        'comments' => [
            ['content' => 'A', 'user_id' => ''],
            ['content' => 'B', 'user_id' => null],
            ['content' => 'C', 'user_id' => '12'],
        ],
    ]);

    expect($errors)->toHaveKey('values.comments.0.user_id');
    expect($errors)->toHaveKey('values.comments.1.user_id');
    expect($errors)->not->toHaveKey('values.comments.2.user_id');
    expect($errors['values.comments.0.user_id'][0])->toBe('User id is required.');
});
