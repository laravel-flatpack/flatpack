<?php

declare(strict_types=1);

use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Flatpack\Widgets\Contracts\WidgetDataProvider;
use Flatpack\Widgets\Data\MetricTrend;
use Flatpack\Widgets\Data\Status;
use Flatpack\Widgets\Payloads\MetricWidgetData;
use Flatpack\Widgets\Payloads\StatusWidgetData;
use Flatpack\Widgets\WidgetContext;
use Illuminate\Http\Request;

uses(TestCase::class);

test('metric providers can return arrayable normalized payloads', function () {
    $provider = new class implements WidgetDataProvider
    {
        public function authorize(Illuminate\Contracts\Auth\Authenticatable $user, WidgetContext $context): bool
        {
            return true;
        }

        public function handle(WidgetContext $context): Illuminate\Contracts\Support\Arrayable
        {
            return new MetricWidgetData(
                value: 1250,
                trend: new MetricTrend(
                    direction: 'up',
                    percent: 12.5,
                    comment: 'Trending up this month',
                ),
                description: 'Revenue trend for the last 6 months',
            );
        }
    };

    $request = Request::create('/flatpack');
    $request->setUserResolver(static fn () => User::factory()->createOne());

    $payload = $provider->handle(new WidgetContext(
        request: $request,
        entity: 'dashboard',
        widgetId: 'total_revenue',
        definition: ['type' => 'metric', 'provider' => 'total_revenue'],
    ));

    $payload = $payload->toArray();

    expect($payload['value'])->toBe(1250)
        ->and($payload['trend']['direction'])->toBe('up')
        ->and($payload['trend']['percent'])->toBe(12.5)
        ->and($payload['trend']['comment'])->toBe('Trending up this month')
        ->and($payload['description'])->toBe('Revenue trend for the last 6 months');
});

test('status widget payload stays arrayable with standard shape', function () {
    $payload = new StatusWidgetData(
        status: Status::SUCCESS,
        value: '99.99%',
        context: 'Availability over last 24h',
        updated_at: '2m ago',
    );

    expect($payload->toArray())->toBe([
        'status' => 'success',
        'value' => '99.99%',
        'context' => 'Availability over last 24h',
        'updated_at' => '2m ago',
    ]);
});
