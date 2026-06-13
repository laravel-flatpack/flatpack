<?php

declare(strict_types=1);

use Flatpack\Services\SaveRecord\RelationSyncErrorMapper;
use Flatpack\Tests\TestCase;
use Illuminate\Database\QueryException;

uses(TestCase::class);

test('relation sync error mapper maps NOT NULL query error to nested field key', function () {
    $mapper = new RelationSyncErrorMapper();

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
                    ],
                ],
            ],
        ],
    ];
    $values = [
        'comments' => [
            ['content' => 'Missing', 'user_id' => ''],
        ],
    ];

    $queryException = new QueryException(
        'sqlite',
        'insert into "post_comments" ("user_id") values (?)',
        [],
        new PDOException('NOT NULL constraint failed: post_comments.user_id'),
    );

    $result = $mapper->mapRequiredConstraint($queryException, $schema, $values);

    expect($result)->toBe([
        'field' => 'values.comments.0.user_id',
        'message' => 'User id is required.',
    ]);
});
