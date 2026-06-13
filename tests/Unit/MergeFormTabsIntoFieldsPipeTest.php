<?php

declare(strict_types=1);

use Flatpack\Schema\Forms\Normalization\FormSchemaPipelineState;
use Flatpack\Schema\Forms\Normalization\Pipes\MergeFormTabsIntoFieldsPipe;

it('merges tabs into fields and replaces tabs with tab_panels', function (): void {
    $pipe = new MergeFormTabsIntoFieldsPipe;
    $state = new FormSchemaPipelineState([
        'tabs' => [
            'profile' => [
                'label' => 'Profile',
                'icon' => 'user',
                'fields' => [
                    'name' => [
                        'type' => 'text',
                        'label' => 'Name',
                    ],
                ],
            ],
            'settings' => [
                'label' => 'Settings',
                'fields' => [
                    'status' => [
                        'type' => 'select',
                        'label' => 'Status',
                        'options' => [
                            ['value' => 'a', 'label' => 'A'],
                        ],
                    ],
                ],
            ],
        ],
    ], null);

    $pipe->handle($state, fn ($s) => $s);

    expect($state->schema)->not->toHaveKey('tabs')
        ->and($state->schema)->toHaveKey('tab_panels')
        ->and($state->schema['fields'])->toHaveKeys(['name', 'status'])
        ->and($state->schema['tab_panels'])->toHaveCount(2)
        ->and($state->schema['tab_panels'][0]['id'])->toBe('profile')
        ->and($state->schema['tab_panels'][0]['icon'])->toBe('user')
        ->and($state->schema['tab_panels'][0]['field_ids'])->toBe(['name'])
        ->and($state->schema['tab_panels'][1]['field_ids'])->toBe(['status']);
});

it('merges root fields then tab fields', function (): void {
    $pipe = new MergeFormTabsIntoFieldsPipe;
    $state = new FormSchemaPipelineState([
        'fields' => [
            'top' => ['type' => 'text', 'label' => 'Top'],
        ],
        'tabs' => [
            't1' => [
                'label' => 'T1',
                'fields' => [
                    'nested' => ['type' => 'text', 'label' => 'Nested'],
                ],
            ],
        ],
    ], null);

    $pipe->handle($state, fn ($s) => $s);

    expect($state->schema['fields'])->toHaveKeys(['top', 'nested']);
});
