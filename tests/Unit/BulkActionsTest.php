<?php

declare(strict_types=1);

use Flatpack\Schema\Lists\BulkActions;
use Flatpack\Support\CompositionDebugLog;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

test('fromSchema omits unconfigured bulk action handlers and logs a debug warning', function () {
    $debug = new CompositionDebugLog('posts/list.yaml');

    $actions = BulkActions::fromSchema([
        'bulk_actions' => [
            'delete' => [
                'label' => 'Delete',
                'action' => 'delete',
            ],
            'publish' => [
                'label' => 'Publish',
                'action' => 'publish',
            ],
        ],
    ], $debug);

    expect($actions)->toHaveCount(1)
        ->and($actions[0]['id'])->toBe('delete')
        ->and($debug->all())->toContain(
            '[posts/list.yaml] Action "publish" in bulk_actions.publish.action is not configured in flatpack.bulk_actions (omitted from UI).'
        );
});
