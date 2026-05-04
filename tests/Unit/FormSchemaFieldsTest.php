<?php

declare(strict_types=1);

use Flatpack\Schema\Forms\FormSchemaFields;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

it('fieldDefinitionById returns null when schema is null', function (): void {
    expect(FormSchemaFields::fieldDefinitionById(null, 'x'))->toBeNull();
});

it('fieldDefinitionById returns null when fields are missing or not an array', function (): void {
    expect(FormSchemaFields::fieldDefinitionById([], 'x'))->toBeNull()
        ->and(FormSchemaFields::fieldDefinitionById(['fields' => 'nope'], 'x'))->toBeNull();
});

it('fieldDefinitionById matches by explicit id', function (): void {
    $schema = [
        'fields' => [
            'a' => ['type' => 'text', 'id' => 'title', 'label' => 'T'],
        ],
    ];

    $def = FormSchemaFields::fieldDefinitionById($schema, 'title');
    expect($def)->not->toBeNull()
        ->and($def['label'])->toBe('T');
});

it('fieldDefinitionById matches yaml key when id is omitted', function (): void {
    $schema = [
        'fields' => [
            'slug' => ['type' => 'text', 'label' => 'Slug'],
        ],
    ];

    expect(FormSchemaFields::fieldDefinitionById($schema, 'slug')['label'])->toBe('Slug');
});

it('fieldDefinitionById skips non-array field definitions', function (): void {
    $schema = [
        'fields' => [
            'bad' => 'nope',
            'good' => ['type' => 'text', 'label' => 'Ok'],
        ],
    ];

    expect(FormSchemaFields::fieldDefinitionById($schema, 'good'))->not->toBeNull();
});

it('embeddedTableColumnById returns null when columns missing', function (): void {
    expect(FormSchemaFields::embeddedTableColumnById([], 'x'))->toBeNull();
});

it('embeddedTableColumnById returns null for blank column id', function (): void {
    $table = ['columns' => [['id' => 'a', 'label' => 'A']]];

    expect(FormSchemaFields::embeddedTableColumnById($table, '  '))->toBeNull();
});

it('embeddedTableColumnById finds column in list-shaped columns', function (): void {
    $table = [
        'columns' => [
            ['id' => 'sku', 'label' => 'SKU'],
            ['id' => 'qty', 'label' => 'Qty'],
        ],
    ];

    $col = FormSchemaFields::embeddedTableColumnById($table, 'qty');
    expect($col)->not->toBeNull()
        ->and($col['label'])->toBe('Qty');
});

it('embeddedTableColumnById skips non-array entries in list columns', function (): void {
    $table = [
        'columns' => [
            'not-array',
            ['id' => 'only', 'label' => 'One'],
        ],
    ];

    expect(FormSchemaFields::embeddedTableColumnById($table, 'only')['label'])->toBe('One');
});

it('embeddedTableColumnById finds column in map-shaped columns by key', function (): void {
    $table = [
        'columns' => [
            'sku' => ['label' => 'SKU'],
            'qty' => ['id' => 'qty', 'label' => 'Qty'],
        ],
    ];

    expect(FormSchemaFields::embeddedTableColumnById($table, 'sku')['label'])->toBe('SKU')
        ->and(FormSchemaFields::embeddedTableColumnById($table, 'qty')['label'])->toBe('Qty');
});

it('embeddedTableColumnById matches id inside definition when key is numeric-like', function (): void {
    $table = [
        'columns' => [
            0 => ['id' => 'a', 'label' => 'A'],
        ],
    ];

    expect(FormSchemaFields::embeddedTableColumnById($table, 'a')['label'])->toBe('A');
});
