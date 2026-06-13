<?php

declare(strict_types=1);

use Flatpack\Schema\Forms\Normalization\FieldSpanCanonicalizer;
use Flatpack\Support\CompositionDebugLog;

describe('FieldSpanCanonicalizer', function () {
    it('canonicalizes fraction aliases', function () {
        expect(FieldSpanCanonicalizer::canonical('1/2'))->toBe('half');
        expect(FieldSpanCanonicalizer::canonical('2/3'))->toBe('two_thirds');
        expect(FieldSpanCanonicalizer::canonical('1/3'))->toBe('third');
        expect(FieldSpanCanonicalizer::canonical('1/4'))->toBe('quarter');
    });

    it('accepts named tokens', function () {
        expect(FieldSpanCanonicalizer::canonical('full'))->toBe('full');
        expect(FieldSpanCanonicalizer::canonical('half'))->toBe('half');
    });

    it('returns null for invalid values', function () {
        expect(FieldSpanCanonicalizer::canonical('bogus'))->toBeNull();
        expect(FieldSpanCanonicalizer::canonical(''))->toBeNull();
        expect(FieldSpanCanonicalizer::canonical(null))->toBeNull();
    });

    it('applyToDefinition mutates valid span and strips invalid', function () {
        $debug = new CompositionDebugLog('');
        $def = ['span' => '1/2', 'type' => 'text'];
        FieldSpanCanonicalizer::applyToDefinition($def, 'ctx', $debug);
        expect($def['span'])->toBe('half');

        $bad = ['span' => 'nope', 'type' => 'text'];
        FieldSpanCanonicalizer::applyToDefinition($bad, 'ctx', $debug);
        expect($bad)->not->toHaveKey('span');
        expect($debug->all())->not->toBeEmpty();
    });

    it('mergeIntoIfPresent copies canonical span into target', function () {
        $target = ['type' => 'metric'];
        FieldSpanCanonicalizer::mergeIntoIfPresent(
            ['span' => '1/4'],
            $target,
            'widgets.x',
            null,
        );
        expect($target['span'])->toBe('quarter');
    });
});
