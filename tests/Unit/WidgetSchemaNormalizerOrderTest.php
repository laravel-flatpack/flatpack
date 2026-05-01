<?php

declare(strict_types=1);

use Flatpack\Schema\Widgets\Normalization\WidgetSchemaNormalizationSupport;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;

test('normalized widgets map preserves YAML declaration order across mixed types', function () {
    $normalizer = new WidgetSchemaNormalizer(new WidgetSchemaNormalizationSupport());
    $out = $normalizer->normalize([
        'widgets' => [
            'first_metric' => [
                'type' => 'metric',
                'provider' => 'orders_count',
                'label' => 'Orders',
            ],
            'second_chart' => [
                'type' => 'chart',
                'provider' => 'traffic_area_chart',
                'label' => 'Traffic',
                'chart' => [
                    'series' => [
                        ['key' => 'a', 'label' => 'A'],
                    ],
                ],
            ],
            'third_table' => [
                'type' => 'table',
                'provider' => 'recent_orders',
            ],
            'fourth_status' => [
                'type' => 'status',
                'provider' => 'queue_health',
                'label' => 'Queue',
            ],
        ],
    ]);

    expect($out)->toBeArray()
        ->and(array_keys($out['widgets']))->toBe([
            'first_metric',
            'second_chart',
            'third_table',
            'fourth_status',
        ]);
});
