<?php

declare(strict_types=1);

use Flatpack\Schema\Forms\Normalization\FormFieldDefinitionNormalizer;

describe('FormFieldDefinitionNormalizer span', function () {
    it('canonicalizes span on fields', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'text',
                'label' => 'Title',
                'span' => '1/3',
            ],
            'title',
            null,
        );
        expect($out)->not->toBeNull();
        expect($out['span'])->toBe('third');
    });

    it('canonicalizes span on nested repeater fields', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'repeater',
                'label' => 'Items',
                'fields' => [
                    'name' => [
                        'type' => 'text',
                        'label' => 'Name',
                        'span' => 'half',
                    ],
                ],
            ],
            'items',
            null,
        );
        expect($out)->not->toBeNull();
        /** @var array<string, mixed> $fields */
        $fields = $out['fields'];
        expect($fields['name']['span'])->toBe('half');
    });

    it('canonicalizes span on embedded table edit_form_field', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'table',
                'label' => 'Lines',
                'relation' => 'lines',
                'columns' => [
                    'sku' => [
                        'label' => 'SKU',
                        'edit_form_field' => [
                            'type' => 'text',
                            'span' => '2/3',
                        ],
                    ],
                ],
            ],
            'lines',
            null,
        );
        expect($out)->not->toBeNull();
        /** @var array<string, mixed> $cols */
        $cols = $out['columns'];
        expect($cols['sku']['edit_form_field']['span'])->toBe('two_thirds');
    });

    it('trims fieldset on fields', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'text',
                'label' => 'Title',
                'fieldset' => '  Meta  ',
            ],
            'title',
            null,
        );
        expect($out)->not->toBeNull();
        expect($out['fieldset'])->toBe(['label' => 'Meta']);
    });

    it('removes invalid fieldset values', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'text',
                'label' => 'Title',
                'fieldset' => 123,
            ],
            'title',
            null,
        );
        expect($out)->not->toBeNull();
        expect($out)->not->toHaveKey('fieldset');
    });

    it('canonicalizes object fieldset with icon', function () {
        $n = new FormFieldDefinitionNormalizer;
        $out = $n->normalize(
            [
                'type' => 'text',
                'label' => 'Title',
                'fieldset' => [
                    'label' => 'Publishing',
                    'icon' => 'clock',
                ],
            ],
            'title',
            null,
        );
        expect($out)->not->toBeNull();
        expect($out['fieldset'])->toBe([
            'label' => 'Publishing',
            'icon' => 'clock',
        ]);
    });
});
