<?php

declare(strict_types=1);

use Flatpack\Schema\Forms\NormalizedFormSchema;

it('exposes schema via toArray and ArrayAccess for string keys', function (): void {
    $n = new NormalizedFormSchema(['fields' => ['a' => ['type' => 'text']]]);
    expect($n->toArray())->toHaveKey('fields')
        ->and(isset($n['fields']))->toBeTrue()
        ->and($n['fields'])->toBeArray();
});

it('ArrayAccess offsetExists is false for non-string offsets', function (): void {
    $n = new NormalizedFormSchema(['x' => 1]);
    expect(isset($n[0]))->toBeFalse();
});

it('ArrayAccess offsetGet returns null for unknown or non-string keys', function (): void {
    $n = new NormalizedFormSchema(['x' => 1]);
    expect($n['missing'])->toBeNull()
        ->and($n[null])->toBeNull();
});

it('ArrayAccess mutations throw LogicException', function (): void {
    $n = new NormalizedFormSchema([]);

    expect(fn () => $n['k'] = 'v')->toThrow(\LogicException::class);
    expect(function () use ($n): void {
        unset($n['k']);
    })->toThrow(\LogicException::class);
});

it('sidebarWidgetDefinitionsRaw returns staged definitions when provided', function (): void {
    $w = ['stats' => ['type' => 'metric']];
    $n = new NormalizedFormSchema([], $w);
    expect($n->sidebarWidgetDefinitionsRaw())->toBe($w);
});
