<?php

declare(strict_types=1);

use Flatpack\Services\Runtime\WidgetRuntime;
use Flatpack\Support\Exceptions\WidgetRuntimeException;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Flatpack\Widgets\Contracts\WidgetDataProvider;
use Flatpack\Widgets\Data\MetricTrend;
use Flatpack\Widgets\Payloads\MetricWidgetData;
use Flatpack\Widgets\WidgetContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;

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

final class TestWidgetProvider implements WidgetDataProvider
{
    public function authorize(Illuminate\Contracts\Auth\Authenticatable $user, WidgetContext $context): bool
    {
        return true;
    }

    public function handle(WidgetContext $context): Illuminate\Contracts\Support\Arrayable
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

    public function handle(WidgetContext $context): Illuminate\Contracts\Support\Arrayable
    {
        return new Fluent([]);
    }
}
