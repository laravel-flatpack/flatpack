<?php

declare(strict_types=1);

use Flatpack\Navigation\ConfigMenuSource;
use Illuminate\Config\Repository;

test('fromConfigItems defaults navOrder to 99 when nav_order is omitted', function () {
    $source = new ConfigMenuSource(new Repository([
        'flatpack' => [
            'ui' => [
                'allow_external_navigation_urls' => false,
            ],
        ],
    ]));

    $items = $source->fromConfigItems([
        'a' => [
            'name' => 'A',
            'url' => '/flatpack/a',
        ],
    ]);

    expect($items)->toHaveCount(1)
        ->and($items[0]->navOrder)->toBe(99);
});

test('fromConfigItems reads integer nav_order', function () {
    $source = new ConfigMenuSource(new Repository([
        'flatpack' => [
            'ui' => [
                'allow_external_navigation_urls' => false,
            ],
        ],
    ]));

    $items = $source->fromConfigItems([
        'x' => [
            'name' => 'X',
            'url' => '/flatpack/x',
            'nav_order' => 3,
        ],
    ]);

    expect($items[0]->navOrder)->toBe(3);
});

test('fromConfigItems casts numeric string nav_order', function () {
    $source = new ConfigMenuSource(new Repository([
        'flatpack' => [
            'ui' => [
                'allow_external_navigation_urls' => false,
            ],
        ],
    ]));

    $items = $source->fromConfigItems([
        'x' => [
            'name' => 'X',
            'url' => '/flatpack/x',
            'nav_order' => '12',
        ],
    ]);

    expect($items[0]->navOrder)->toBe(12);
});

test('fromConfigItems treats non-numeric nav_order as default', function () {
    $source = new ConfigMenuSource(new Repository([
        'flatpack' => [
            'ui' => [
                'allow_external_navigation_urls' => false,
            ],
        ],
    ]));

    $items = $source->fromConfigItems([
        'x' => [
            'name' => 'X',
            'url' => '/flatpack/x',
            'nav_order' => 'nope',
        ],
    ]);

    expect($items[0]->navOrder)->toBe(99);
});
