<?php

declare(strict_types=1);

use Flatpack\Schema\Lists\FilterDefinition;
use Flatpack\Schema\Lists\FilterProcessor;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Schema::table('posts', static function (Blueprint $table): void {
        $table->string('order')->nullable();
    });
});

test('applyToQuery wraps reserved-word column identifiers for filters', function (): void {
    $definitions = [
        new FilterDefinition(
            id: 'order',
            label: 'Order',
            placeholder: '',
            type: 'select',
            multiple: false,
            options: [
                ['value' => 'first', 'label' => 'First'],
            ],
        ),
    ];
    $values = ['order' => 'first'];

    $schema = [
        'columns' => [
            'order' => ['label' => 'Order'],
            'title' => ['label' => 'Title'],
        ],
        'filters' => [
            'order' => [
                'type' => 'select',
                'options' => [
                    ['value' => 'first', 'label' => 'First'],
                ],
            ],
        ],
    ];

    $match = Post::factory()->create([
        'title' => 'Has order',
        'slug' => 'has-order',
    ]);
    $match->forceFill(['order' => 'first']);
    $match->saveQuietly();

    $other = Post::factory()->create([
        'title' => 'Other',
        'slug' => 'other-slug',
    ]);
    $other->forceFill(['order' => 'zzz']);
    $other->saveQuietly();

    $query = Post::query();
    FilterProcessor::applyToQuery($query, $definitions, $values, $schema);

    expect($query->count())->toBe(1);
    expect($query->first()?->title)->toBe('Has order');
});
