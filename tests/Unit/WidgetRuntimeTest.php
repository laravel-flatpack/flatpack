<?php

declare(strict_types=1);

use Flatpack\Contracts\Widgets\WidgetDataProvider;
use Flatpack\Services\Runtime\WidgetRuntime;
use Flatpack\Support\Exceptions\WidgetRuntimeException;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Flatpack\Widgets\WidgetDataContext;
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
        new WidgetDataContext(
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
        new WidgetDataContext(
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
    public function authorize(Illuminate\Contracts\Auth\Authenticatable $user, WidgetDataContext $context): bool
    {
        return true;
    }

    public function handle(WidgetDataContext $context): array
    {
        return [
            'value' => 1250.0,
            'trend' => [
                'percent' => 12.5,
                'direction' => 'up',
                'comment' => 'Revenue is up this month',
            ],
        ];
    }
}

final class DeniedWidgetProvider implements WidgetDataProvider
{
    public function authorize(Illuminate\Contracts\Auth\Authenticatable $user, WidgetDataContext $context): bool
    {
        return false;
    }

    public function handle(WidgetDataContext $context): array
    {
        return [];
    }
}
