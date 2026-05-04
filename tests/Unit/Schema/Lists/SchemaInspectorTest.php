<?php

declare(strict_types=1);

use Flatpack\Schema\Lists\SchemaInspector;

it('collects column keys from list-shaped and map-shaped columns', function (): void {
    $list = [
        'columns' => [
            ['id' => 'title', 'type' => 'text'],
            ['id' => 'body', 'type' => 'text'],
        ],
    ];
    $map = [
        'columns' => [
            'slug' => ['label' => 'Slug', 'type' => 'text'],
        ],
    ];

    expect(SchemaInspector::columnKeys($list))->toBe(['title', 'body'])
        ->and(SchemaInspector::columnKeys($map))->toBe(['slug']);
});

it('extracts relation column definitions', function (): void {
    $schema = [
        'columns' => [
            [
                'id' => 'category_id',
                'type' => 'relation',
                'relation' => 'category',
                'relation_name' => 'name',
                'relation_value' => 'id',
            ],
            [
                'id' => 'title',
                'type' => 'text',
            ],
        ],
    ];

    $defs = SchemaInspector::relationColumnDefinitions($schema);

    expect($defs)->toHaveCount(1)
        ->and($defs[0]->relation)->toBe('category');
});

it('dedupes relation names', function (): void {
    $defs = SchemaInspector::relationColumnDefinitions([
        'columns' => [
            [
                'id' => 'a',
                'type' => 'relation',
                'relation' => 'cat',
                'relation_name' => 'name',
                'relation_value' => 'id',
            ],
            [
                'id' => 'b',
                'type' => 'relation',
                'relation' => 'cat',
                'relation_name' => 'title',
                'relation_value' => 'id',
            ],
        ],
    ]);

    expect(SchemaInspector::uniqueRelationNames($defs))->toBe(['cat']);
});

it('builds searchable definitions for columns and relations', function (): void {
    $schema = [
        'columns' => [
            'title' => [
                'id' => 'title',
                'type' => 'text',
                'searchable' => true,
            ],
            'cat' => [
                'id' => 'cat',
                'type' => 'relation',
                'searchable' => true,
                'relation' => 'category',
                'relation_name' => 'name',
            ],
            'broken' => [
                'id' => 'broken',
                'type' => 'relation',
                'searchable' => true,
            ],
        ],
    ];

    $defs = SchemaInspector::searchableColumnDefinitions($schema);

    expect($defs)->toHaveCount(2);
});

it('builds filter definitions for select and date filters', function (): void {
    $schema = [
        'columns' => [
            'status' => [
                'id' => 'status',
                'type' => 'text',
                'label' => 'Status',
                'options' => [
                    ['value' => 'a', 'label' => 'A'],
                ],
            ],
            'created' => [
                'id' => 'created',
                'type' => 'date',
                'label' => 'Created',
            ],
        ],
        'filters' => [
            'status' => [
                'type' => 'select',
            ],
            'created' => [
                'type' => 'date',
                'mode' => 'from',
            ],
        ],
    ];

    $filters = SchemaInspector::filterDefinitions($schema);

    expect($filters)->toHaveCount(2)
        ->and($filters[0]->type)->toBe('select')
        ->and($filters[1]->type)->toBe('date');
});

it('returns sortable column ids', function (): void {
    $schema = [
        'columns' => [
            'title' => [
                'id' => 'title',
                'sortable' => true,
            ],
            'body' => [
                'id' => 'body',
            ],
        ],
    ];

    expect(SchemaInspector::sortableColumnIds($schema))->toBe(['title']);
});
