<?php

declare(strict_types=1);

use Flatpack\Services\Runtime\WidgetRuntime;
use Flatpack\Support\Exceptions\WidgetRuntimeException;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Flatpack\Widgets\Contracts\WidgetDataProvider;
use Flatpack\Widgets\Data\MetricTrend;
use Flatpack\Widgets\Data\MetricWidgetData;
use Flatpack\Widgets\Data\WidgetPayload;
use Flatpack\Widgets\WidgetContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

uses(TestCase::class);

test('resolveProvider throws runtime exception when provider is missing', function () {
    config()->set('flatpack.widget_providers', [
        'total_revenue' => TestWidgetProvider::class,
    ]);

    expect(fn () => app(WidgetRuntime::class)->resolveProvider('missing_provider'))
        ->toThrow(WidgetRuntimeException::class, 'Flatpack widget provider "missing_provider" is not configured.');
});

test('resolveData throws authorization exception when provider denies access', function () {
    config()->set('flatpack.widget_providers', [
        'denied_provider' => DeniedWidgetProvider::class,
    ]);

    $provider = app(WidgetRuntime::class)->resolveProvider('denied_provider');
    $request = Request::create('/flatpack');
    $user = User::factory()->createOne();
    $request->setUserResolver(static fn () => $user);

    expect(fn () => app(WidgetRuntime::class)->resolveData(
        $provider,
        new WidgetContext(
            request: $request,
            entity: 'dashboard',
            widgetId: 'revenue',
            definition: ['type' => 'metric', 'provider' => 'denied_provider'],
        ),
    ))->toThrow(AuthorizationException::class);
});

test('resolveData throws when payload type does not match widget type', function () {
    config()->set('flatpack.widget_providers', [
        'bad_chart' => BadChartWidgetProvider::class,
    ]);

    $provider = app(WidgetRuntime::class)->resolveProvider('bad_chart');
    $request = Request::create('/flatpack');
    $user = User::factory()->createOne();
    $request->setUserResolver(static fn () => $user);

    expect(fn () => app(WidgetRuntime::class)->resolveData(
        $provider,
        new WidgetContext(
            request: $request,
            entity: 'dashboard',
            widgetId: 'traffic',
            definition: ['type' => 'chart', 'provider' => 'bad_chart'],
        ),
    ))->toThrow(WidgetRuntimeException::class, 'ChartWidgetData');
});

test('resolveData rejects mismatched payload for grid widget with provider', function () {
    config()->set('flatpack.widget_providers', [
        'bad_grid' => BadChartWidgetProvider::class,
    ]);

    $provider = app(WidgetRuntime::class)->resolveProvider('bad_grid');
    $request = Request::create('/flatpack');
    $user = User::factory()->createOne();
    $request->setUserResolver(static fn () => $user);

    expect(fn () => app(WidgetRuntime::class)->resolveData(
        $provider,
        new WidgetContext(
            request: $request,
            entity: 'dashboard',
            widgetId: 'recent_posts',
            definition: ['type' => 'grid', 'provider' => 'bad_grid'],
        ),
    ))->toThrow(WidgetRuntimeException::class, 'TableWidgetData');
});

test('resolveData returns provider payload', function () {
    config()->set('flatpack.widget_providers', [
        'total_revenue' => TestWidgetProvider::class,
    ]);

    $provider = app(WidgetRuntime::class)->resolveProvider('total_revenue');
    $request = Request::create('/flatpack');
    $user = User::factory()->createOne();
    $request->setUserResolver(static fn () => $user);

    $data = app(WidgetRuntime::class)->resolveData(
        $provider,
        new WidgetContext(
            request: $request,
            entity: 'dashboard',
            widgetId: 'revenue',
            definition: ['type' => 'metric', 'provider' => 'total_revenue'],
        ),
    );

    expect($data['value'])->toBe(1250.0)
        ->and($data['trend']['percent'])->toBe(12.5)
        ->and($data['trend']['direction'])->toBe('up');
});

final class BadChartWidgetProvider implements WidgetDataProvider
{
    public function authorize(Illuminate\Contracts\Auth\Authenticatable $user, WidgetContext $context): bool
    {
        return true;
    }

    public function handle(WidgetContext $context): WidgetPayload
    {
        return new MetricWidgetData(
            value: 1,
            trend: new MetricTrend(direction: 'flat', percent: 0),
            description: null,
        );
    }
}

final class TestWidgetProvider implements WidgetDataProvider
{
    public function authorize(Illuminate\Contracts\Auth\Authenticatable $user, WidgetContext $context): bool
    {
        return true;
    }

    public function handle(WidgetContext $context): WidgetPayload
    {
        return new MetricWidgetData(
            value: 1250.0,
            trend: new MetricTrend(
                direction: 'up',
                percent: 12.5,
                comment: 'Revenue is up this month',
            ),
            description: 'Revenue trend',
        );
    }
}

final class DeniedWidgetProvider implements WidgetDataProvider
{
    public function authorize(Illuminate\Contracts\Auth\Authenticatable $user, WidgetContext $context): bool
    {
        return false;
    }

    public function handle(WidgetContext $context): WidgetPayload
    {
        return new MetricWidgetData(
            value: 0,
            trend: new MetricTrend(direction: 'flat', percent: 0),
            description: null,
        );
    }
}
