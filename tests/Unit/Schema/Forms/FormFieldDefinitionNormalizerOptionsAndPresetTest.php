<?php

declare(strict_types=1);

use Flatpack\Schema\Forms\Normalization\FormFieldDefinitionNormalizer;
use Flatpack\Support\CompositionDebugLog;

describe('FormFieldDefinitionNormalizer options and preset edge cases', function () {
    it('normalizes select options from associative map', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    'draft' => 'Draft',
                    'live' => 'Published',
                ],
            ],
            'status',
            null,
        );

        expect($out)->not->toBeNull()
            ->and($out['options'][0])->toMatchArray(['value' => 'draft', 'label' => 'Draft']);
    });

    it('preserves status and icon on list-shaped options', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'select',
                'label' => 'Queue',
                'options' => [
                    [
                        'value' => 'open',
                        'label' => 'Open',
                        'status' => 'success',
                        'icon' => 'check',
                    ],
                ],
            ],
            'queue',
            null,
        );

        expect($out['options'][0])->toMatchArray([
            'value' => 'open',
            'label' => 'Open',
            'status' => 'success',
            'icon' => 'check',
        ]);
    });

    it('drops preset on non-text fields and logs', function () {
        $log = new CompositionDebugLog('');
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'select',
                'label' => 'Role',
                'preset' => ['field' => 'x', 'type' => 'route-parameter'],
            ],
            'role',
            $log,
        );

        expect($out)->not->toBeNull()
            ->and($out)->not->toHaveKey('preset')
            ->and($log->all())->not->toBeEmpty();
    });

    it('removes invalid preset objects with log detail', function () {
        $log = new CompositionDebugLog('');
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'text',
                'label' => 'Slug',
                'preset' => ['field' => '', 'type' => 'route-parameter'],
            ],
            'slug',
            $log,
        );

        expect($out)->not->toBeNull()
            ->and($out)->not->toHaveKey('preset');
    });

    it('sets combobox remote when relation is present', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'combobox',
                'label' => 'Author',
                'relation' => 'author',
            ],
            'author_id',
            null,
        );

        expect($out['remote'] ?? false)->toBeTrue();
    });
});
