<?php

declare(strict_types=1);

use Flatpack\Composition\CompositionValues;

test('navOrder returns default when data is null', function () {
    $values = new CompositionValues;

    expect($values->navOrder(null))->toBe(99);
});

test('navOrder returns default when key is missing', function () {
    $values = new CompositionValues;

    expect($values->navOrder(['model' => 'App\\Models\\Post']))->toBe(99);
});

test('navOrder casts integer root value', function () {
    $values = new CompositionValues;

    expect($values->navOrder(['nav_order' => 42]))->toBe(42);
});

test('navOrder casts numeric string root value', function () {
    $values = new CompositionValues;

    expect($values->navOrder(['nav_order' => '15']))->toBe(15);
});

test('navOrder ignores non-numeric root value', function () {
    $values = new CompositionValues;

    expect($values->navOrder(['nav_order' => 'first']))->toBe(99);
});
