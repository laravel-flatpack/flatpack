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
