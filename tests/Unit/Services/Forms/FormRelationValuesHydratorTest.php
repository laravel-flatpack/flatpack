<?php

declare(strict_types=1);

use Flatpack\Services\Forms\FormRelationValuesHydrator;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\PostComment;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

test('hydrate respects yaml limit and never returns more rows than the hard max', function (): void {
    $post = Post::factory()->create(['title' => 'Test', 'slug' => 'test']);

    PostComment::factory()->count(5)->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->getKey(),
    ]);

    $post->load('comments');

    $hydrator = new FormRelationValuesHydrator;
    $fieldDef = [
        'relation' => 'comments',
        'limit' => 3,
        'columns' => [['id' => 'content']],
    ];

    $rows = $hydrator->hydrate($post, 'comments', $fieldDef, null);

    expect($rows)->toHaveCount(3);
});

test('hydrate enforces hard max even when enforce_relation_table_limit config is false', function (): void {
    config(['flatpack.forms.enforce_relation_table_limit' => false]);
    config(['flatpack.forms.relation_table_hard_max_rows' => 2]);

    $post = Post::factory()->create(['title' => 'Test2', 'slug' => 'test2']);

    PostComment::factory()->count(5)->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->getKey(),
    ]);

    $post->load('comments');

    $hydrator = new FormRelationValuesHydrator;
    $fieldDef = [
        'relation' => 'comments',
        'columns' => [['id' => 'content']],
    ];

    $rows = $hydrator->hydrate($post, 'comments', $fieldDef, null);

    expect($rows)->toHaveCount(2);
});
