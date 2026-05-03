<?php

declare(strict_types=1);

use Flatpack\Schema\Forms\FormSubmitActionAllowed;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

test('defaults to save when schema has no actions', function () {
    expect(FormSubmitActionAllowed::allowedActionStrings([]))->toBe(['save']);
});

test('includes top-level form actions registered in flatpack.actions', function () {
    $schema = [
        'actions' => [
            'save' => [
                'label' => 'Save',
                'action' => 'save',
                'submit' => true,
            ],
            'extra' => [
                'label' => 'Delete',
                'action' => 'delete',
                'submit' => true,
            ],
        ],
    ];
    expect(FormSubmitActionAllowed::allowedActionStrings($schema))
        ->toContain('save')
        ->toContain('delete');
});

test('includes toolbar field actions for submit validation', function () {
    $schema = [
        'fields' => [
            'main_toolbar' => [
                'type' => 'toolbar',
                'actions' => [
                    [
                        'id' => 'publish_row',
                        'label' => 'Publish',
                        'icon' => '',
                        'variant' => 'default',
                        'action' => 'publish_post',
                        'submit' => true,
                    ],
                ],
            ],
        ],
    ];
    expect(FormSubmitActionAllowed::allowedActionStrings($schema))->toContain(
        'publish_post',
    );
});
