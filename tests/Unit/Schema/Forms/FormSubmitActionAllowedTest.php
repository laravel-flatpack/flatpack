<?php

declare(strict_types=1);

use Flatpack\Schema\Forms\FormCompositionMergeForPersistence;
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

test('toolbar inside tab panel fields is allowlisted after form schema merge', function () {
    $raw = [
        'tabs' => [
            'main' => [
                'label' => 'Main',
                'fields' => [
                    'tab_toolbar' => [
                        'id' => 'tab_toolbar',
                        'type' => 'toolbar',
                        'actions' => [
                            [
                                'id' => 'from_tab',
                                'label' => 'Save from tab',
                                'action' => 'save_from_tab',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    expect(FormSubmitActionAllowed::allowedActionStrings($raw))->not->toContain('save_from_tab');

    $merged = FormCompositionMergeForPersistence::merge($raw);
    expect($merged)->not->toBeNull();
    expect(FormSubmitActionAllowed::allowedActionStrings($merged))->toContain('save_from_tab');
});

test('toolbar nested under sidebar is allowlisted only after form schema merge', function () {
    $raw = [
        'sidebar' => [
            'toolbar' => [
                'type' => 'toolbar',
                'actions' => [
                    [
                        'id' => 'publish',
                        'label' => 'Publish',
                        'action' => 'publish_post',
                    ],
                ],
            ],
        ],
    ];

    expect(FormSubmitActionAllowed::allowedActionStrings($raw))->not->toContain('publish_post');

    $merged = FormCompositionMergeForPersistence::merge($raw);
    expect($merged)->not->toBeNull();
    expect(FormSubmitActionAllowed::allowedActionStrings($merged))->toContain('publish_post');
});
