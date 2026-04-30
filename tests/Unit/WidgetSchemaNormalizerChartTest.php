<?php

declare(strict_types=1);

use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;

test('chart widget is normalized with defaults', function () {
    $normalizer = new WidgetSchemaNormalizer();
    $out = $normalizer->normalize([
        'widgets' => [
            't' => [
                'type' => 'chart',
                'provider' => 'traffic_area_chart',
                'label' => 'Visitors',
                'chart' => [
                    'series' => [
                        ['key' => 'a', 'label' => 'A'],
                    ],
                ],
            ],
        ],
    ]);

    expect($out['widgets']['t']['type'])->toBe('chart')
        ->and($out['widgets']['t']['chart']['mode'])->toBe('area')
        ->and($out['widgets']['t']['chart']['x_key'])->toBe('date')
        ->and($out['widgets']['t']['chart']['variant'])->toBe('area_stacked')
        ->and($out['widgets']['t']['chart'])->not->toHaveKey('time_ranges');
});

test('chart widget normalizes series semantic colors', function () {
    $normalizer = new WidgetSchemaNormalizer();
    $out = $normalizer->normalize([
        'widgets' => [
            't' => [
                'type' => 'chart',
                'provider' => 'traffic_area_chart',
                'label' => 'Visitors',
                'chart' => [
                    'x_key' => 'date',
                    'series' => [
                        ['key' => 'desktop', 'label' => 'Desktop', 'color' => 'success'],
                        ['key' => 'mobile', 'label' => 'Mobile', 'color' => 'info'],
                    ],
                ],
            ],
        ],
    ]);

    expect($out['widgets']['t']['chart']['series'][0]['color'])->toBe('success')
        ->and($out['widgets']['t']['chart']['series'][1]['color'])->toBe('info');
});

test('chart widget is skipped without series', function () {
    $normalizer = new WidgetSchemaNormalizer();
    $out = $normalizer->normalize([
        'widgets' => [
            't' => [
                'type' => 'chart',
                'provider' => 'x',
                'label' => 'X',
                'chart' => [
                    'x_key' => 'date',
                    'series' => [],
                ],
            ],
        ],
    ]);

    expect($out['widgets'])->toBeEmpty();
});

test('chart widget supports bar mode', function () {
    $normalizer = new WidgetSchemaNormalizer();
    $out = $normalizer->normalize([
        'widgets' => [
            't' => [
                'type' => 'chart',
                'provider' => 'traffic_area_chart',
                'label' => 'Visitors',
                'chart' => [
                    'x_key' => 'date',
                    'mode' => 'bar',
                    'series' => [
                        ['key' => 'desktop', 'label' => 'Desktop'],
                    ],
                ],
            ],
        ],
    ]);

    expect($out['widgets']['t']['chart']['mode'])->toBe('bar')
        ->and($out['widgets']['t']['chart']['variant'])->toBe('area_stacked');
});

test('chart widget is skipped with invalid mode', function () {
    $normalizer = new WidgetSchemaNormalizer();
    $out = $normalizer->normalize([
        'widgets' => [
            't' => [
                'type' => 'chart',
                'provider' => 'x',
                'label' => 'X',
                'chart' => [
                    'x_key' => 'date',
                    'mode' => 'pie',
                    'series' => [
                        ['key' => 'a', 'label' => 'A'],
                    ],
                ],
            ],
        ],
    ]);

    expect($out['widgets'])->toBeEmpty();
});
