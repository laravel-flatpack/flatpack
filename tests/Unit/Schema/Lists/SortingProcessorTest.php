<?php

declare(strict_types=1);

use Flatpack\Schema\Lists\SortingProcessor;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

test('normalizeAndApply falls back to primary key desc when requested column is not sortable', function (): void {
    $first = Post::factory()->create(['title' => 'First', 'slug' => 'first']);
    $second = Post::factory()->create(['title' => 'Second', 'slug' => 'second']);

    $model = new Post;
    $query = Post::query();

    SortingProcessor::normalizeAndApply(
        query: $query,
        sortBy: 'injected; DROP TABLE posts--',
        sortDirection: 'asc',
        sortableColumns: ['title', 'slug'],
        modelKeyName: $model->getKeyName(),
        qualifiedModelKeyName: $model->getQualifiedKeyName(),
    );

    // Invalid column rejected; falls back to id DESC — newest first
    expect($query->first()?->getKey())->toBe($second->getKey())
        ->and($query->count())->toBe(2);
});

test('normalizeAndApply orders by requested column when it is in the sortable list', function (): void {
    Post::factory()->create(['title' => 'Zebra', 'slug' => 'zebra']);
    Post::factory()->create(['title' => 'Apple', 'slug' => 'apple']);

    $model = new Post;
    $query = Post::query();

    SortingProcessor::normalizeAndApply(
        query: $query,
        sortBy: 'title',
        sortDirection: 'asc',
        sortableColumns: ['title'],
        modelKeyName: $model->getKeyName(),
        qualifiedModelKeyName: $model->getQualifiedKeyName(),
    );

    expect($query->pluck('title')->first())->toBe('Apple');
});

test('normalize returns model key as default when no sort column requested', function (): void {
    $result = SortingProcessor::normalize(
        sortBy: null,
        sortDirection: 'asc',
        sortableColumns: ['title'],
        modelKeyName: 'id',
    );

    expect($result['sort_by'])->toBe('id')
        ->and($result['sort_direction'])->toBe('desc');
});
