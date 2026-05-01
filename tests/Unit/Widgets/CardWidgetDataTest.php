<?php

declare(strict_types=1);

use Flatpack\Widgets\Data\CardWidgetData;
use Flatpack\Widgets\Data\WidgetPayload;

test('CardWidgetData toArray omits null properties and toJson matches', function () {
    $data = new CardWidgetData;

    expect($data)->toBeInstanceOf(WidgetPayload::class)
        ->and($data->toArray())->toBe([])
        ->and(json_decode($data->toJson(), true, flags: JSON_THROW_ON_ERROR))->toBe([]);
});

test('CardWidgetData includes only set non-null fields', function () {
    $full = new CardWidgetData(
        value: 42,
        context: 'Last 7 days',
        footer: 'vs prior week',
    );

    expect($full->toArray())->toBe([
        'value' => 42,
        'context' => 'Last 7 days',
        'footer' => 'vs prior week',
    ]);
});

test('CardWidgetData keeps zero as value and can omit optional strings', function () {
    $zero = new CardWidgetData(value: 0);
    expect($zero->toArray())->toBe(['value' => 0]);

    $partial = new CardWidgetData(value: '—', context: 'N/A');
    expect($partial->toArray())->toBe([
        'value' => '—',
        'context' => 'N/A',
    ]);
});
