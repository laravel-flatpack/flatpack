<?php

declare(strict_types=1);

use Flatpack\Schema\Lists\NormalizedListSchema;

it('exposes schema via toArray and ArrayAccess for string keys', function (): void {
    $n = new NormalizedListSchema(['columns' => []]);
    expect($n->toArray())->toHaveKey('columns')
        ->and(isset($n['columns']))->toBeTrue();
});

it('ArrayAccess offsetExists is false for non-string offsets', function (): void {
    $n = new NormalizedListSchema(['x' => 1]);
    expect(isset($n[true]))->toBeFalse();
});

it('ArrayAccess offsetGet returns null for unknown or non-string keys', function (): void {
    $n = new NormalizedListSchema(['x' => 1]);
    expect($n['missing'])->toBeNull()
        ->and($n[1.5])->toBeNull();
});

it('ArrayAccess mutations throw LogicException', function (): void {
    $n = new NormalizedListSchema([]);

    expect(fn () => $n[] = 'x')->toThrow(LogicException::class);
    expect(function () use ($n): void {
        unset($n['a']);
    })->toThrow(LogicException::class);
});
