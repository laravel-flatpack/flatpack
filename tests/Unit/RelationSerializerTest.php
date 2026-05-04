<?php

declare(strict_types=1);

use Flatpack\Schema\Lists\RelationSerializer;
use Flatpack\Tests\Models\Category;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

it('serializePayload returns null for null related', function (): void {
    expect(RelationSerializer::serializePayload(null, 'name', 'id'))->toBeNull();
});

it('serializePayload maps a single model', function (): void {
    /** @var Category $cat */
    $cat = Category::factory()->createOne(['name' => 'Books']);

    $payload = RelationSerializer::serializePayload($cat, 'name', 'id');

    expect($payload)->toBeArray()
        ->and($payload['id'])->toBe($cat->getKey())
        ->and($payload['name'])->toBe('Books');
});

it('serializePayload maps a collection of models', function (): void {
    $cats = Category::factory()->count(2)->create();

    $payload = RelationSerializer::serializePayload($cats, 'name', 'id');

    expect($payload)->toBeArray()->toHaveCount(2)
        ->and($payload[0])->toHaveKeys(['id', 'name']);
});

it('serializePayload filters non-models out of collections', function (): void {
    $cat = Category::factory()->createOne();
    $collection = collect([$cat, 'bad', null]);

    $payload = RelationSerializer::serializePayload($collection, 'name', 'id');

    expect($payload)->toHaveCount(1);
});

it('serializePayload returns null for unrelated values', function (): void {
    expect(RelationSerializer::serializePayload('nope', 'name', 'id'))->toBeNull();
});
