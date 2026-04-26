<?php

declare(strict_types=1);

use Flatpack\Schema\Lists\BulkActions;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

test('fromSchema includes optional success_message and confirm', function () {
    $actions = BulkActions::fromSchema([
        'bulk_actions' => [
            'delete' => [
                'label' => 'Delete',
                'action' => 'delete',
                'variant' => 'destructive',
                'success_message' => 'Removed',
                'confirm' => true,
            ],
            'unconfigured' => [
                'label' => 'Missing handler',
                'action' => 'not-a-real-bulk-action',
            ],
        ],
    ]);

    expect($actions)->toHaveCount(1);
    expect($actions[0])->toMatchArray([
        'id' => 'delete',
        'success_message' => 'Removed',
        'confirm' => true,
    ]);
});

test('fromSchema includes normalized enabled_if for bulk actions', function () {
    $actions = BulkActions::fromSchema([
        'bulk_actions' => [
            'archive' => [
                'label' => 'Archive',
                'action' => 'delete',
                'enabled_if' => [
                    'all' => [
                        ['list.selection.min' => 1],
                        ['list.filters_applied' => false],
                    ],
                    'message' => 'Select at least one row.',
                ],
            ],
        ],
    ]);

    expect($actions[0]['enabled_if'] ?? null)->toMatchArray([
        'all' => [
            ['list.selection.min' => 1],
            ['list.filters_applied' => false],
        ],
        'message' => 'Select at least one row.',
    ]);
});

test('fromSchema includes normalized visible_if for bulk actions', function () {
    $actions = BulkActions::fromSchema([
        'bulk_actions' => [
            'archive' => [
                'label' => 'Archive',
                'action' => 'delete',
                'visible_if' => [
                    'any' => [
                        ['list.search_present' => true],
                    ],
                ],
            ],
        ],
    ]);

    expect($actions[0]['visible_if'] ?? null)->toMatchArray([
        'any' => [
            ['list.search_present' => true],
        ],
    ]);
});
