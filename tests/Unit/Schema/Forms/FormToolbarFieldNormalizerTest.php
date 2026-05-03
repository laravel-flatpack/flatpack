<?php

declare(strict_types=1);

use Flatpack\Schema\Forms\Normalization\FormFieldDefinitionNormalizer;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

describe('FormFieldDefinitionNormalizer toolbar', function () {
    it('infers toolbar when actions block is present without type', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize([
            'actions' => [
                'go' => [
                    'label' => 'Go',
                    'href' => '/posts/create',
                ],
            ],
        ], 'toolbar_row', null);

        expect($out)->not->toBeNull();
        expect($out['type'])->toBe('toolbar');
        expect($out['actions'])->toHaveCount(1);
        expect($out['actions'][0])->toMatchArray([
            'id' => 'toolbar_row:go',
            'label' => 'Go',
            'href' => '/posts/create',
        ]);
        expect($out['align'])->toBe('right');
    });

    it('accepts align spaced and invalid align defaults to right', function () {
        $n = new FormFieldDefinitionNormalizer;
        $spaced = $n->normalize([
            'type' => 'toolbar',
            'align' => 'spaced',
            'actions' => [
                'a' => ['label' => 'A', 'href' => '/a'],
            ],
        ], 't1', null);
        expect($spaced)->not->toBeNull();
        expect($spaced['align'])->toBe('spaced');

        $fallback = $n->normalize([
            'type' => 'toolbar',
            'align' => 'not-real',
            'actions' => [
                'a' => ['label' => 'A', 'href' => '/a'],
            ],
        ], 't2', null);
        expect($fallback)->not->toBeNull();
        expect($fallback['align'])->toBe('right');
    });

    it('keeps toolbar rows when handlers are missing (handler_missing flag)', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize([
            'type' => 'toolbar',
            'actions' => [
                'bad' => [
                    'label' => 'Bad',
                    'action' => 'definitely_not_a_configured_flatpack_action_xyz',
                ],
            ],
        ], 'empty_toolbar', null);

        expect($out)->not->toBeNull();
        expect($out['actions'])->toHaveCount(1);
        expect($out['actions'][0]['handler_missing'] ?? false)->toBeTrue();
        expect($out['actions'][0]['action'])->toBe('definitely_not_a_configured_flatpack_action_xyz');
    });
});
