<?php

declare(strict_types=1);

use Flatpack\Schema\RelationFieldQuery;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

it('components returns null when model class is empty or not a Model subclass', function (): void {
    expect(RelationFieldQuery::components('', ['relation' => 'category']))->toBeNull()
        ->and(RelationFieldQuery::components(\stdClass::class, [
            'relation' => 'category',
            'relation_name' => 'name',
            'relation_value' => 'id',
        ]))->toBeNull()
        ->and(RelationFieldQuery::components('Not\\A\\Class', [
            'relation' => 'category',
            'relation_name' => 'name',
            'relation_value' => 'id',
        ]))->toBeNull();
});

it('components returns null when relation metadata is incomplete', function (): void {
    expect(RelationFieldQuery::components(Post::class, [
        'relation' => 'category',
    ]))->toBeNull()
        ->and(RelationFieldQuery::components(Post::class, [
            'relation' => '',
            'relation_name' => 'name',
            'relation_value' => 'id',
        ]))->toBeNull();
});

it('components returns null when the relation method is missing', function (): void {
    expect(RelationFieldQuery::components(Post::class, [
        'relation' => 'missingRelation',
        'relation_name' => 'name',
        'relation_value' => 'id',
    ]))->toBeNull();
});

it('components resolves BelongsTo query and column names', function (): void {
    $resolved = RelationFieldQuery::components(Post::class, [
        'relation' => 'category',
        'relation_name' => 'name',
        'relation_value' => 'id',
    ]);

    expect($resolved)->toBeArray()->toHaveCount(3);
    expect($resolved[1])->toBe('name')
        ->and($resolved[2])->toBe('id');
});

it('stringFromField reads camelCase or snake_case keys', function (): void {
    expect(RelationFieldQuery::stringFromField([
        'relation_name' => 'title',
    ], 'relation_name', 'relationName'))->toBe('title')
        ->and(RelationFieldQuery::stringFromField([
            'relationName' => 'title',
        ], 'relation_name', 'relationName'))->toBe('title');
});
