<?php

declare(strict_types=1);

use Flatpack\Services\Lists\Dto\ListQueryParams;
use Flatpack\Services\Lists\ListRecordsLoader;
use Flatpack\Tests\Models\Category;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(TestCase::class, RefreshDatabase::class);

test('list records eager-load relations in a bounded number of queries', function () {
    $category = Category::factory()->create(['name' => 'News']);
    Post::factory()->count(5)->create(['category_id' => $category->id]);

    $schema = [
        'columns' => [
            'id' => ['label' => 'ID'],
            'title' => ['label' => 'Title'],
            'category_id' => [
                'label' => 'Category',
                'type' => 'relation',
                'relation' => 'category',
                'relation_name' => 'name',
                'relation_value' => 'id',
            ],
        ],
    ];

    $loader = app(ListRecordsLoader::class);

    DB::enableQueryLog();
    $result = $loader->load(Post::class, $schema, new ListQueryParams(page: 1, perPage: 15));
    $records = $result['records'];
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($records)->toHaveCount(5);
    expect($result['pagination']['total'])->toBe(5);
    expect($records[0]['category'])->toMatchArray([
        'id' => $category->id,
        'name' => 'News',
    ]);
    // Paginate runs a COUNT query, then posts + batched categories (no N+1 per row).
    expect($queryCount)->toBe(3);
});
