<?php

declare(strict_types=1);

use Flatpack\Schema\Forms\FormSchemaNormalizer;
use Flatpack\Tests\Models\Category;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

it('returns empty array when model or schema is missing', function (): void {
    $n = new FormSchemaNormalizer;

    expect($n->formValuesFromModel(null, null))->toBe([]);
});

it('builds values from a model using simple and relation-backed combobox fields', function (): void {
    $category = Category::factory()->createOne(['name' => 'News']);
    /** @var Post $post */
    $post = Post::factory()->createOne(['category_id' => $category->getKey()]);

    $normalizer = new FormSchemaNormalizer;
    $normalized = $normalizer->normalizedFormSchema([
        'fields' => [
            'title' => [
                'type' => 'text',
                'label' => 'Title',
            ],
            'category_id' => [
                'type' => 'combobox',
                'label' => 'Category',
                'relation' => 'category',
                'relation_name' => 'name',
                'relation_value' => 'id',
            ],
        ],
    ], null, Post::class, $post);

    expect($normalized)->not->toBeNull();
    $values = $normalizer->formValuesFromModel($post, $normalized);

    expect($values['title'])->toBe($post->title)
        ->and($values['category_id'])->toBe((string) $category->getKey());
});
