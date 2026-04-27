<?php

declare(strict_types=1);

use Flatpack\Schema\Lists\Normalization\ListSchemaPipelineState;
use Flatpack\Schema\Lists\Normalization\Pipes\MergeListTabsIntoColumnsPipe;

it('merges tab columns into columns and replaces tabs with tab_panels', function (): void {
    $pipe = new MergeListTabsIntoColumnsPipe;
    $state = new ListSchemaPipelineState([
        'tabs' => [
            'main' => [
                'label' => 'Main',
                'columns' => [
                    [
                        'id' => 'title',
                        'type' => 'text',
                        'label' => 'Title',
                    ],
                ],
            ],
            'meta' => [
                'label' => 'Meta',
                'icon' => 'cog',
                'columns' => [
                    [
                        'id' => 'status',
                        'type' => 'text',
                        'label' => 'Status',
                    ],
                ],
            ],
        ],
    ], null);

    $pipe->handle($state, fn ($s) => $s);

    expect($state->schema)->not->toHaveKey('tabs')
        ->and($state->schema)->toHaveKey('tab_panels')
        ->and($state->schema['columns'])->toHaveCount(2)
        ->and($state->schema['tab_panels'])->toHaveCount(2)
        ->and($state->schema['tab_panels'][0]['column_ids'])->toBe(['title'])
        ->and($state->schema['tab_panels'][1]['column_ids'])->toBe(['status']);
});

it('merges root columns then tab-only columns', function (): void {
    $pipe = new MergeListTabsIntoColumnsPipe;
    $state = new ListSchemaPipelineState([
        'columns' => [
            [
                'id' => 'id',
                'type' => 'text',
                'label' => 'ID',
            ],
        ],
        'tabs' => [
            'extra' => [
                'label' => 'Extra',
                'columns' => [
                    [
                        'id' => 'note',
                        'type' => 'text',
                        'label' => 'Note',
                    ],
                ],
            ],
        ],
    ], null);

    $pipe->handle($state, fn ($s) => $s);

    expect($state->schema['columns'])->toHaveCount(2);
});

it('uses root columns when a tab omits columns and keeps scope metadata', function (): void {
    $pipe = new MergeListTabsIntoColumnsPipe;
    $state = new ListSchemaPipelineState([
        'columns' => [
            [
                'id' => 'title',
                'type' => 'text',
                'label' => 'Title',
            ],
        ],
        'tabs' => [
            'records' => [
                'label' => 'Records',
            ],
            'trashed' => [
                'label' => 'Trash',
                'scope' => 'trashed',
            ],
        ],
    ], null);

    $pipe->handle($state, fn ($s) => $s);

    expect($state->schema['tab_panels'][0]['column_ids'])->toBe(['title'])
        ->and($state->schema['tab_panels'][1]['column_ids'])->toBe(['title'])
        ->and($state->schema['tab_panels'][1]['scope'])->toBe('trashed');
});

it('preserves inferred ids when map-shaped columns are flattened to a list', function (): void {
    $pipe = new MergeListTabsIntoColumnsPipe;
    $state = new ListSchemaPipelineState([
        'columns' => [
            'title' => [
                'label' => 'Title',
                'type' => 'text',
            ],
        ],
        'tabs' => [
            'trash' => [
                'label' => 'Trash',
                'columns' => [
                    'deleted_at' => [
                        'label' => 'Deleted At',
                        'type' => 'date',
                    ],
                ],
            ],
        ],
    ], null);

    $pipe->handle($state, fn ($s) => $s);

    expect($state->schema['columns'])->toHaveCount(2)
        ->and($state->schema['columns'][0]['id'])->toBe('title')
        ->and($state->schema['columns'][1]['id'])->toBe('deleted_at')
        ->and($state->schema['tab_panels'][0]['column_ids'])->toBe(['deleted_at']);
});

it('keeps tab filters and bulk action overrides in tab_panels metadata', function (): void {
    $pipe = new MergeListTabsIntoColumnsPipe;
    $state = new ListSchemaPipelineState([
        'columns' => [
            'title' => [
                'label' => 'Title',
                'type' => 'text',
            ],
        ],
        'tabs' => [
            'drafts' => [
                'label' => 'Drafts',
                'scope' => 'draft',
                'reorderable' => false,
                'filters' => [
                    'status' => [
                        'type' => 'select',
                        'options' => [
                            ['value' => 'draft', 'label' => 'Draft'],
                        ],
                    ],
                ],
                'bulk_actions' => [
                    'publish' => [
                        'label' => 'Publish',
                        'action' => 'publish',
                    ],
                ],
            ],
        ],
    ], null);

    $pipe->handle($state, fn ($s) => $s);

    expect($state->schema['tab_panels'][0]['scope'])->toBe('draft')
        ->and($state->schema['tab_panels'][0]['reorderable'])->toBeFalse()
        ->and($state->schema['tab_panels'][0])->toHaveKey('filters')
        ->and($state->schema['tab_panels'][0])->toHaveKey('bulk_actions');
});
