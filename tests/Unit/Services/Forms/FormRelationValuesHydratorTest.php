<?php

declare(strict_types=1);

use Flatpack\Services\Forms\FormRelationValuesHydrator;
use Flatpack\Support\CompositionDebugLog;
use Flatpack\Tests\Models\Category;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\PostComment;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

test('columnIdsFromDefinition supports list and map column shapes', function (): void {
    expect(FormRelationValuesHydrator::columnIdsFromDefinition([
        'columns' => [
            ['id' => 'a', 'label' => 'A'],
            ['id' => 'b'],
        ],
    ]))->toBe(['a', 'b'])
        ->and(FormRelationValuesHydrator::columnIdsFromDefinition([
            'columns' => [
                'sku' => ['label' => 'SKU'],
                'qty' => ['label' => 'Qty'],
            ],
        ]))->toBe(['sku', 'qty'])
        ->and(FormRelationValuesHydrator::columnIdsFromDefinition([]))->toBe([]);
});

test('nestedRelationNamesForEagerLoad collects nested relation columns', function (): void {
    expect(FormRelationValuesHydrator::nestedRelationNamesForEagerLoad([
        'columns' => [
            [
                'id' => 'user',
                'type' => 'relation',
                'relation' => 'author',
            ],
            [
                'id' => 'title',
                'type' => 'text',
            ],
        ],
    ]))->toBe(['author']);
});

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

test('hydrate BelongsToMany rows including pivot attributes when present', function (): void {
    $post = Post::factory()->create(['title' => 'Many', 'slug' => 'many']);
    $a = Category::factory()->createOne(['name' => 'A']);
    $b = Category::factory()->createOne(['name' => 'B']);
    $post->categories()->attach([$a->getKey(), $b->getKey()]);
    $post->load('categories');

    $hydrator = new FormRelationValuesHydrator;
    $rows = $hydrator->hydrate($post, 'categories', [
        'relation' => 'categories',
        'columns' => [
            ['id' => 'name', 'label' => 'Name'],
        ],
    ], null);

    expect($rows)->toHaveCount(2);
});

test('hydrate HasOne as a single-element row list', function (): void {
    $post = Post::factory()->create(['title' => 'One', 'slug' => 'one']);
    $post->meta()->create(['subtitle' => 'Hello meta']);
    $post->load('meta');

    $hydrator = new FormRelationValuesHydrator;
    $rows = $hydrator->hydrate($post, 'meta', [
        'relation' => 'meta',
        'columns' => [
            ['id' => 'subtitle', 'label' => 'Subtitle'],
        ],
    ], null);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['subtitle'] ?? null)->toBe('Hello meta');
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

test('columnIdsFromDefinition handles invalid column containers and sparse list entries', function (): void {
    expect(FormRelationValuesHydrator::columnIdsFromDefinition(['columns' => 'nope']))->toBe([])
        ->and(FormRelationValuesHydrator::columnIdsFromDefinition([
            'columns' => [
                'not-an-array',
                ['id' => '  ok  '],
                ['id' => ''],
                ['label' => 'no-id'],
            ],
        ]))->toBe(['ok']);
});

test('nestedRelationNamesForEagerLoad supports map-style columns', function (): void {
    expect(FormRelationValuesHydrator::nestedRelationNamesForEagerLoad([
        'columns' => [
            'title' => ['type' => 'text'],
            'owner' => [
                'type' => 'relation',
                'relation' => 'author',
            ],
        ],
    ]))->toBe(['author']);
});

test('hydrate returns empty array when relation key missing or blank', function (): void {
    $post = Post::factory()->create(['title' => 'X', 'slug' => 'x']);
    $hydrator = new FormRelationValuesHydrator;

    expect($hydrator->hydrate($post, 'tags', [], null))->toBe([])
        ->and($hydrator->hydrate($post, 'tags', ['relation' => '  '], null))->toBe([]);
});

test('hydrate logs and skips when model has no relation method', function (): void {
    $post = Post::factory()->create(['title' => 'Y', 'slug' => 'y']);
    $log = new CompositionDebugLog('');
    $hydrator = new FormRelationValuesHydrator;

    expect($hydrator->hydrate($post, 'field', ['relation' => 'missingRelation'], $log))->toBe([])
        ->and(collect($log->all())->contains(fn (string $m): bool => str_contains($m, 'has no relation method')))->toBeTrue();
});

test('hydrate logs unsupported relation kinds such as BelongsTo', function (): void {
    $post = Post::factory()->create(['title' => 'Z', 'slug' => 'z']);
    $log = new CompositionDebugLog('');
    $hydrator = new FormRelationValuesHydrator;

    $rows = $hydrator->hydrate($post, 'category_field', [
        'relation' => 'category',
        'columns' => [['id' => 'name']],
    ], $log);

    expect($rows)->toBe([])
        ->and(collect($log->all())->contains(fn (string $m): bool => str_contains($m, 'not supported for hydration')))->toBeTrue();
});

test('hydrate lazy-loads collection relations when not eager-loaded and records debug line', function (): void {
    $post = Post::factory()->create(['title' => 'Lazy', 'slug' => 'lazy']);
    PostComment::factory()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->getKey(),
        'content' => 'Hi',
    ]);

    $log = new CompositionDebugLog('');
    $hydrator = new FormRelationValuesHydrator;

    $rows = $hydrator->hydrate($post, 'comments', [
        'relation' => 'comments',
        'columns' => [['id' => 'content']],
    ], $log);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['content'] ?? null)->toBe('Hi')
        ->and(collect($log->all())->contains(fn (string $m): bool => str_contains($m, 'not eager-loaded')))->toBeTrue();
});

test('hydrate lazy-loads HasOne when not eager-loaded', function (): void {
    $post = Post::factory()->create(['title' => 'LazyOne', 'slug' => 'lazy-one']);
    $post->meta()->create(['subtitle' => 'Sub']);

    $log = new CompositionDebugLog('');
    $hydrator = new FormRelationValuesHydrator;

    $rows = $hydrator->hydrate($post, 'meta', [
        'relation' => 'meta',
        'columns' => [['id' => 'subtitle']],
    ], $log);

    expect($rows)->toHaveCount(1)
        ->and(collect($log->all())->contains(fn (string $m): bool => str_contains($m, 'not eager-loaded')))->toBeTrue();
});

test('hydrate maps nested BelongsTo payloads on relation columns using camelCase keys', function (): void {
    $user = User::factory()->createOne(['name' => 'Nested User']);
    $post = Post::factory()->create(['title' => 'Nest', 'slug' => 'nest']);
    PostComment::factory()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->getKey(),
        'user_id' => $user->getKey(),
        'content' => 'c',
    ]);
    $post->load('comments');

    $hydrator = new FormRelationValuesHydrator;
    $rows = $hydrator->hydrate($post, 'comments', [
        'relation' => 'comments',
        'columns' => [
            ['id' => 'content'],
            [
                'id' => 'author',
                'type' => 'relation',
                'relation' => 'user',
                'relationName' => 'name',
                'relationValue' => 'id',
            ],
        ],
    ], null);

    expect($rows[0]['user'] ?? null)->toMatchArray([
        'id' => (string) $user->getKey(),
        'name' => 'Nested User',
    ]);
});

test('hydrate sets nested relation column to null when nested relation does not return a single model', function (): void {
    $post = Post::factory()->create(['title' => 'Multi', 'slug' => 'multi']);
    $cat = Category::factory()->createOne(['name' => 'C', 'slug' => 'c-slug']);
    $post->categories()->attach($cat->getKey());
    $post->load('categories');

    $hydrator = new FormRelationValuesHydrator;
    $rows = $hydrator->hydrate($post, 'categories', [
        'relation' => 'categories',
        'columns' => [
            ['id' => 'name'],
            [
                'id' => 'posts_col',
                'type' => 'relation',
                'relation' => 'posts',
                'relation_name' => 'title',
            ],
        ],
    ], null);

    expect($rows[0])->toHaveKey('posts')
        ->and($rows[0]['posts'])->toBeNull();
});

test('hydrate uses relation_value as primary key column when building rows', function (): void {
    $post = Post::factory()->create(['title' => 'SlugRow', 'slug' => 'slug-row']);
    $cat = Category::factory()->createOne(['name' => 'Cat', 'slug' => 'cat-slug']);
    $post->categories()->attach($cat->getKey());
    $post->load('categories');

    $hydrator = new FormRelationValuesHydrator;
    $rows = $hydrator->hydrate($post, 'categories', [
        'relation' => 'categories',
        'relation_value' => 'slug',
        'relation_name' => 'name',
        'columns' => [
            ['id' => 'name'],
        ],
    ], null);

    expect($rows[0])->toMatchArray([
        'slug' => 'cat-slug',
        'id' => (string) $cat->getKey(),
        'name' => 'Cat',
    ]);
});

test('treats relation_table_hard_max_rows below one as 500', function (): void {
    config(['flatpack.forms.relation_table_hard_max_rows' => 0]);

    $post = Post::factory()->create(['title' => 'Hard', 'slug' => 'hard']);
    PostComment::factory()->count(3)->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->getKey(),
    ]);
    $post->load('comments');

    $hydrator = new FormRelationValuesHydrator;
    $rows = $hydrator->hydrate($post, 'comments', [
        'relation' => 'comments',
        'limit' => 9999,
        'columns' => [['id' => 'content']],
    ], null);

    expect($rows)->toHaveCount(3);
});
