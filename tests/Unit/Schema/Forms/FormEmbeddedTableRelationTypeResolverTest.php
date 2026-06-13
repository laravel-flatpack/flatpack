<?php

declare(strict_types=1);

use Flatpack\Schema\Forms\FormEmbeddedTableRelationTypeResolver;
use Flatpack\Support\CompositionDebugLog;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

it('returns null when schema is null', function (): void {
    $r = new FormEmbeddedTableRelationTypeResolver;

    expect($r->enrichFormSchema(null, Post::class))->toBeNull();
});

it('returns schema unchanged when model class is invalid', function (): void {
    $r = new FormEmbeddedTableRelationTypeResolver;
    $schema = ['fields' => ['t' => ['type' => 'table', 'relation' => 'x', 'columns' => [['id' => 'a']]]]];

    expect($r->enrichFormSchema($schema, ''))->toBe($schema);
});

it('skips enrichment when table_relation_type is already authoritatively set', function (): void {
    $r = new FormEmbeddedTableRelationTypeResolver;
    $schema = [
        'fields' => [
            'lines' => [
                'type' => 'table',
                'relation' => 'comments',
                'table_relation_type' => 'custom',
                'columns' => [['id' => 'body', 'label' => 'Body']],
            ],
        ],
    ];

    $out = $r->enrichFormSchema($schema, Post::class);

    expect($out['fields']['lines']['table_relation_type'])->toBe('custom');
});

it('resolves MorphMany BelongsToMany and HasOne relations on Post', function (): void {
    $r = new FormEmbeddedTableRelationTypeResolver;
    $schema = [
        'fields' => [
            'comments' => [
                'type' => 'table',
                'relation' => 'comments',
                'columns' => [['id' => 'body', 'label' => 'Body']],
            ],
            'cats' => [
                'type' => 'table',
                'relation' => 'categories',
                'columns' => [['id' => 'name', 'label' => 'Name']],
            ],
            'meta_block' => [
                'type' => 'table',
                'relation' => 'meta',
                'columns' => [['id' => 'key', 'label' => 'Key']],
            ],
        ],
    ];

    $out = $r->enrichFormSchema($schema, Post::class);

    expect($out['fields']['comments']['table_relation_type'])->toBe('morph_many')
        ->and($out['fields']['cats']['table_relation_type'])->toBe('belongs_to_many')
        ->and($out['fields']['meta_block']['table_relation_type'])->toBe('has_one');
});

it('logs and marks unknown when relation cannot be resolved', function (): void {
    $r = new FormEmbeddedTableRelationTypeResolver;
    $log = new CompositionDebugLog('');
    $schema = [
        'fields' => [
            'orphan' => [
                'type' => 'table',
                'relation' => 'totallyMissingRelation',
                'columns' => [['id' => 'x', 'label' => 'X']],
            ],
        ],
    ];

    $out = $r->enrichFormSchema($schema, Post::class, null, $log);

    expect($out['fields']['orphan']['table_relation_type'])->toBe('unknown')
        ->and($log->all())->not->toBeEmpty();
});
