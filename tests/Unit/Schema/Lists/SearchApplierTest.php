<?php

declare(strict_types=1);

use Flatpack\Schema\Lists\SearchApplier;
use Flatpack\Schema\Lists\SearchDefinition;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

it('apply is a no-op when there are no searchable definitions', function (): void {
    $query = Post::query();
    $sqlBefore = $query->toSql();

    SearchApplier::apply($query, [], 'hello');

    expect($query->toSql())->toBe($sqlBefore);
});

it('apply adds LIKE condition for a column search definition', function (): void {
    $query = Post::query();
    SearchApplier::apply($query, [SearchDefinition::forColumn('title')], 'hello%_!');

    $sql = strtolower($query->toSql());
    expect($sql)->toContain('like');
    expect($query->getBindings())->not->toBeEmpty();
});

it('apply ORs multiple column conditions', function (): void {
    $query = Post::query();
    SearchApplier::apply($query, [
        SearchDefinition::forColumn('title'),
        SearchDefinition::forColumn('body'),
    ], 'x');

    expect(strtolower($query->toSql()))->toContain(' or ');
});

it('apply skips empty column ids', function (): void {
    $query = Post::query();
    SearchApplier::apply($query, [
        SearchDefinition::forColumn(''),
    ], 'x');

    expect(strtolower($query->toSql()))->not->toContain('like');
});

it('apply adds whereHas for relation search definitions', function (): void {
    $query = Post::query();
    SearchApplier::apply($query, [
        SearchDefinition::forRelation('category', 'name'),
    ], 'news');

    expect(strtolower($query->toSql()))->toContain('exists');
});

it('apply ORs relation and column conditions', function (): void {
    $query = Post::query();
    SearchApplier::apply($query, [
        SearchDefinition::forColumn('title'),
        SearchDefinition::forRelation('category', 'name'),
    ], 'mix');

    expect(strtolower($query->toSql()))->toContain(' or ');
});

it('apply skips relation defs with empty names', function (): void {
    $query = Post::query();
    SearchApplier::apply($query, [
        SearchDefinition::forRelation('', 'name'),
        SearchDefinition::forRelation('category', ''),
    ], 'x');

    expect(strtolower($query->toSql()))->not->toContain('exists');
});
