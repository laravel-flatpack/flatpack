<?php

declare(strict_types=1);

use Flatpack\Support\FieldsetCanonicalizer;

describe('FieldsetCanonicalizer', function () {
    it('normalizes string fieldset to label array', function () {
        $def = ['fieldset' => '  Hello  '];
        FieldsetCanonicalizer::applyToDefinition($def);
        expect($def['fieldset'])->toBe(['label' => 'Hello']);
    });

    it('drops empty strings', function () {
        $def = ['fieldset' => '   '];
        FieldsetCanonicalizer::applyToDefinition($def);
        expect($def)->not->toHaveKey('fieldset');
    });

    it('drops non-string non-array values', function () {
        $def = ['fieldset' => false];
        FieldsetCanonicalizer::applyToDefinition($def);
        expect($def)->not->toHaveKey('fieldset');
    });

    it('normalizes object fieldset with optional icon', function () {
        $def = [
            'fieldset' => [
                'label' => ' Status ',
                'icon' => 'Clock',
            ],
        ];
        FieldsetCanonicalizer::applyToDefinition($def);
        expect($def['fieldset'])->toBe([
            'label' => 'Status',
            'icon' => 'clock',
        ]);
    });

    it('normalizes icon underscores to kebab-case', function () {
        $def = [
            'fieldset' => [
                'label' => 'Content',
                'icon' => 'file_text',
            ],
        ];
        FieldsetCanonicalizer::applyToDefinition($def);
        expect($def['fieldset']['icon'])->toBe('file-text');
    });

    it('omits icon when empty or invalid type', function () {
        $def = ['fieldset' => ['label' => 'X', 'icon' => '   ']];
        FieldsetCanonicalizer::applyToDefinition($def);
        expect($def['fieldset'])->toBe(['label' => 'X']);
    });

    it('persists non-card variants and omits card', function () {
        $def = [
            'fieldset' => [
                'label' => 'A',
                'variant' => 'minimal',
            ],
        ];
        FieldsetCanonicalizer::applyToDefinition($def);
        expect($def['fieldset'])->toBe([
            'label' => 'A',
            'variant' => 'minimal',
        ]);

        $defCard = ['fieldset' => ['label' => 'B', 'variant' => 'card']];
        FieldsetCanonicalizer::applyToDefinition($defCard);
        expect($defCard['fieldset'])->toBe(['label' => 'B']);
    });

    it('drops invalid variant keys', function () {
        $def = ['fieldset' => ['label' => 'Y', 'variant' => 'nope']];
        FieldsetCanonicalizer::applyToDefinition($def);
        expect($def['fieldset'])->toBe(['label' => 'Y']);
    });

    it('persists boolean collapsed when variant is not none', function () {
        $def = [
            'fieldset' => [
                'label' => 'Section',
                'collapsed' => true,
            ],
        ];
        FieldsetCanonicalizer::applyToDefinition($def);
        expect($def['fieldset'])->toBe([
            'label' => 'Section',
            'collapsed' => true,
        ]);

        $defFalse = [
            'fieldset' => [
                'label' => 'Other',
                'collapsed' => false,
            ],
        ];
        FieldsetCanonicalizer::applyToDefinition($defFalse);
        expect($defFalse['fieldset'])->toBe([
            'label' => 'Other',
            'collapsed' => false,
        ]);
    });

    it('omits non-boolean collapsed', function () {
        $def = ['fieldset' => ['label' => 'Z', 'collapsed' => 'yes']];
        FieldsetCanonicalizer::applyToDefinition($def);
        expect($def['fieldset'])->toBe(['label' => 'Z']);
    });

    it('strips collapsed when variant is none', function () {
        $def = [
            'fieldset' => [
                'label' => 'Hidden',
                'variant' => 'none',
                'collapsed' => true,
            ],
        ];
        FieldsetCanonicalizer::applyToDefinition($def);
        expect($def['fieldset'])->toBe([
            'label' => 'Hidden',
            'variant' => 'none',
        ]);
    });
});
