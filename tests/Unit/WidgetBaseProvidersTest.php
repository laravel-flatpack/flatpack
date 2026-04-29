<?php

declare(strict_types=1);

use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Flatpack\Widgets\FlatpackCardWidget;
use Flatpack\Widgets\FlatpackMetricWidget;
use Flatpack\Widgets\WidgetDataContext;
use Illuminate\Http\Request;

uses(TestCase::class);

test('base metric widget returns normalized default payload shape', function () {
    $provider = new class extends FlatpackMetricWidget {};

    $request = Request::create('/flatpack');
    $request->setUserResolver(static fn () => User::factory()->createOne());

    $payload = $provider->handle(new WidgetDataContext(
        request: $request,
        entity: 'dashboard',
        widgetId: 'total_revenue',
        definition: ['type' => 'metric', 'provider' => 'total_revenue'],
    ));

    expect($payload)->toBe([
        'value' => 0,
        'trend' => [
            'direction' => 'flat',
            'percent' => 0,
            'comment' => '',
        ],
    ]);
});

test('base card widget returns normalized default payload shape', function () {
    $provider = new class extends FlatpackCardWidget {};

    $request = Request::create('/flatpack');
    $request->setUserResolver(static fn () => User::factory()->createOne());

    $payload = $provider->handle(new WidgetDataContext(
        request: $request,
        entity: 'dashboard',
        widgetId: 'health_check',
        definition: ['type' => 'card', 'provider' => 'health_check'],
    ));

    expect($payload)->toBe([
        'status' => 'default',
        'value' => 0,
        'context' => '',
        'updated_at' => '',
        'description' => '',
    ]);
});

test('metric base widget allows overriding hooks while preserving shape', function () {
    $provider = new class extends FlatpackMetricWidget
    {
        protected function resolveValue(WidgetDataContext $context): float|int|null
        {
            return 1250;
        }

        protected function resolveTrendDirection(WidgetDataContext $context): string
        {
            return 'up';
        }

        protected function resolveTrendPercent(WidgetDataContext $context): float|int|null
        {
            return 12.5;
        }

        protected function resolveTrendComment(WidgetDataContext $context): string
        {
            return 'Trending up this month';
        }

        protected function resolveDescription(WidgetDataContext $context): ?string
        {
            return 'Revenue trend for the last 6 months';
        }
    };

    $request = Request::create('/flatpack');
    $request->setUserResolver(static fn () => User::factory()->createOne());

    $payload = $provider->handle(new WidgetDataContext(
        request: $request,
        entity: 'dashboard',
        widgetId: 'total_revenue',
        definition: ['type' => 'metric', 'provider' => 'total_revenue'],
    ));

    expect($payload['value'])->toBe(1250)
        ->and($payload['trend']['direction'])->toBe('up')
        ->and($payload['trend']['percent'])->toBe(12.5)
        ->and($payload['trend']['comment'])->toBe('Trending up this month')
        ->and($payload['description'])->toBe('Revenue trend for the last 6 months');
});
