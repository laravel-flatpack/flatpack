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
