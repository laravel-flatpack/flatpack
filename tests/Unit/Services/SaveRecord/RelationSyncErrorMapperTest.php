<?php

declare(strict_types=1);

use Flatpack\Services\SaveRecord\RelationSyncErrorMapper;
use Flatpack\Tests\TestCase;
use Illuminate\Database\QueryException;

uses(TestCase::class);

test('mapRequiredConstraint maps NOT NULL failures to nested table row fields', function (): void {
    $mapper = new RelationSyncErrorMapper;
    $exception = new QueryException(
        'sqlite',
        'insert into …',
        [],
        new \PDOException('NOT NULL constraint failed: line_items.sku'),
    );

    $mapped = $mapper->mapRequiredConstraint(
        $exception,
        [
            'fields' => [
                'lines' => [
                    'id' => 'lines',
                    'type' => 'table',
                    'relation' => 'lines',
                    'columns' => [
                        ['id' => 'sku', 'label' => 'SKU'],
                    ],
                ],
            ],
        ],
        [
            'lines' => [
                ['sku' => ''],
            ],
        ],
    );

    expect($mapped)->not->toBeNull()
        ->and($mapped['field'])->toBe('values.lines.0.sku');
});

test('mapRequiredConstraint returns null when schema or column cannot be matched', function (): void {
    $mapper = new RelationSyncErrorMapper;

    expect($mapper->mapRequiredConstraint(
        new QueryException('sqlite', 'x', [], new \PDOException('other')),
        ['fields' => []],
        [],
    ))->toBeNull();
});
