<?php

declare(strict_types=1);

use Flatpack\Schema\Lists\ListSubmitActionAllowed;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

test('allowedListHeaderActionStrings collects non-href list header actions', function () {
    $schema = [
        'actions' => [
            'create' => [
                'label' => 'Create',
                'action' => 'create',
            ],
            'docs' => [
                'label' => 'Docs',
                'href' => '/docs',
            ],
        ],
    ];

    expect(ListSubmitActionAllowed::allowedListHeaderActionStrings($schema))
        ->toBe(['create']);
});

test('allowedListHeaderActionStrings returns empty when schema has no actions', function () {
    expect(ListSubmitActionAllowed::allowedListHeaderActionStrings([]))->toBe([]);
});

test('allowedRowActionStrings merges form headers list headers and column actions', function () {
    $formSchema = [
        'actions' => [
            'edit_form' => [
                'label' => 'Edit',
                'action' => 'edit',
            ],
        ],
    ];
    $listSchema = [
        'actions' => [
            'create' => [
                'label' => 'Create',
                'action' => 'create',
            ],
        ],
        'columns' => [
            'actions' => [
                'type' => 'actions',
                'actions' => [
                    ['label' => 'Edit', 'action' => 'edit'],
                    ['label' => 'Delete', 'action' => 'delete'],
                ],
            ],
        ],
    ];

    expect(ListSubmitActionAllowed::allowedRowActionStrings($formSchema, $listSchema))
        ->toEqual(['edit', 'create', 'delete']);
});

test('allowedBulkActionStrings uses tab overrides for bulk actions', function () {
    $schema = [
        'bulk_actions' => [
            'delete' => [
                'label' => 'Delete',
                'action' => 'delete',
            ],
        ],
        'tabs' => [
            'records' => [
                'label' => 'Records',
            ],
            'drafts' => [
                'label' => 'Drafts',
                'scope' => 'draftOnly',
                'bulkActions' => [
                    'restore' => [
                        'label' => 'Restore',
                        'action' => 'restore',
                    ],
                ],
            ],
        ],
    ];

    expect(ListSubmitActionAllowed::allowedBulkActionStrings($schema, 'records'))
        ->toBe(['delete']);

    expect(ListSubmitActionAllowed::allowedBulkActionStrings($schema, 'drafts'))
        ->toBe(['restore']);
});

test('allowedBulkActionStrings returns empty for scoped tab without bulk actions', function () {
    $schema = [
        'bulk_actions' => [
            'delete' => [
                'label' => 'Delete',
                'action' => 'delete',
            ],
        ],
        'tabs' => [
            'records' => [
                'label' => 'Records',
            ],
            'drafts' => [
                'label' => 'Drafts',
                'scope' => 'draftOnly',
            ],
        ],
    ];

    expect(ListSubmitActionAllowed::allowedBulkActionStrings($schema, 'drafts'))->toBe([]);
});

test('allowedWidgetRowActionStrings collects widget header and column actions', function () {
    $widget = [
        'actions' => [
            'refresh' => [
                'label' => 'Refresh',
                'action' => 'create',
            ],
        ],
        'columns' => [
            'actions' => [
                'type' => 'actions',
                'actions' => [
                    ['label' => 'Delete', 'action' => 'delete'],
                ],
            ],
        ],
    ];

    expect(ListSubmitActionAllowed::allowedWidgetRowActionStrings($widget))
        ->toEqual(['create', 'delete']);
});

test('allowedWidgetBulkActionStrings supports normalized list bulk actions', function () {
    $widget = [
        'bulk_actions' => [
            [
                'id' => 'delete',
                'label' => 'Delete',
                'action' => 'delete',
            ],
        ],
    ];

    expect(ListSubmitActionAllowed::allowedWidgetBulkActionStrings($widget))->toBe(['delete']);
});

test('allowedWidgetBulkActionStrings supports raw keyed bulk actions map', function () {
    $widget = [
        'bulk_actions' => [
            'delete' => [
                'label' => 'Delete',
                'action' => 'delete',
            ],
        ],
    ];

    expect(ListSubmitActionAllowed::allowedWidgetBulkActionStrings($widget))->toBe(['delete']);
});
