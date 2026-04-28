<?php

declare(strict_types=1);

use Flatpack\Schema\Lists\SearchApplier;
use Flatpack\Schema\Lists\SearchDefinition;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

test('search term with literal percent matches only records containing a percent sign', function (): void {
    $defs = [SearchDefinition::forColumn('title')];

    $match = Post::factory()->create(['title' => 'fifty% off', 'slug' => 'fifty-off']);
    Post::factory()->create(['title' => 'no special chars', 'slug' => 'no-special']);

    $query = Post::query();
    SearchApplier::apply($query, $defs, '%');

    expect($query->count())->toBe(1)
        ->and($query->first()?->getKey())->toBe($match->getKey());
});

test('search term with literal underscore matches only records containing an underscore', function (): void {
    $defs = [SearchDefinition::forColumn('title')];

    $match = Post::factory()->create(['title' => 'some_slug style', 'slug' => 'some-slug']);
    Post::factory()->create(['title' => 'no special chars', 'slug' => 'no-special2']);

    $query = Post::query();
    SearchApplier::apply($query, $defs, '_');

    expect($query->count())->toBe(1)
        ->and($query->first()?->getKey())->toBe($match->getKey());
});

test('normal search term still matches substring', function (): void {
    $defs = [SearchDefinition::forColumn('title')];

    Post::factory()->create(['title' => 'Hello World', 'slug' => 'hello-world']);
    Post::factory()->create(['title' => 'Another Post', 'slug' => 'another-post']);

    $query = Post::query();
    SearchApplier::apply($query, $defs, 'Hello');

    expect($query->count())->toBe(1)
        ->and($query->first()?->title)->toBe('Hello World');
});
