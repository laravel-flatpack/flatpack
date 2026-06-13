<?php

declare(strict_types=1);

use Flatpack\Widgets\Data\ChartWidgetData;
use Flatpack\Widgets\Data\WidgetPayload;

test('ChartWidgetData toArray wraps points and toJson encodes the same shape', function () {
    $points = [
        ['x' => '2024-01', 'y' => 10],
        ['x' => '2024-02', 'y' => 12.5],
        ['x' => '2024-03', 'y' => null],
    ];
    $data = new ChartWidgetData($points);

    expect($data)->toBeInstanceOf(WidgetPayload::class)
        ->and($data->toArray())->toBe(['points' => $points])
        ->and(json_decode($data->toJson(), true, flags: JSON_THROW_ON_ERROR))->toBe($data->toArray());
});

test('ChartWidgetData allows an empty points list', function () {
    $data = new ChartWidgetData([]);

    expect($data->toArray())->toBe(['points' => []]);
});
