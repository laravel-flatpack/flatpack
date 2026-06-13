<?php

declare(strict_types=1);

namespace Flatpack\Services\Runtime;

use Flatpack\Support\Exceptions\WidgetRuntimeException;
use Flatpack\Widgets\Contracts\WidgetDataProvider;
use Flatpack\Widgets\Data\CardWidgetData;
use Flatpack\Widgets\Data\ChartWidgetData;
use Flatpack\Widgets\Data\MetricWidgetData;
use Flatpack\Widgets\Data\StatusWidgetData;
use Flatpack\Widgets\Data\TableWidgetData;
use Flatpack\Widgets\Data\WidgetPayload;
use Flatpack\Widgets\WidgetContext;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class WidgetRuntime
{
    public function resolveProvider(string $provider): WidgetDataProvider
    {
        $provider = trim($provider);
        $providerClass = config("flatpack.widget_providers.{$provider}");
        if (! is_string($providerClass) || $providerClass === '') {
            throw new WidgetRuntimeException(
                404,
                sprintf('Flatpack widget provider "%s" is not configured. Add it to config/flatpack.php under "widget_providers".', $provider),
            );
        }

        $resolved = app()->make($providerClass);
        if (! $resolved instanceof WidgetDataProvider) {
            throw new WidgetRuntimeException(500, 'Flatpack widget provider must implement WidgetDataProvider.');
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveData(WidgetDataProvider $provider, WidgetContext $context): array
    {
        $user = $context->request->user();
        if ($user === null) {
            throw new AuthorizationException('This widget is not authorized.');
        }

        if (! $provider->authorize($user, $context)) {
            throw new AuthorizationException('This widget is not authorized.');
        }

        $data = $provider->handle($context);

        $this->assertPayloadMatchesWidgetType($context, $data);

        return $data->toArray();
    }

    private function assertPayloadMatchesWidgetType(WidgetContext $context, WidgetPayload $payload): void
    {
        $type = trim((string) ($context->definition['type'] ?? ''));
        $hasProvider = trim((string) ($context->definition['provider'] ?? '')) !== '';

        $expected = match ($type) {
            'metric' => MetricWidgetData::class,
            'card' => CardWidgetData::class,
            'status' => StatusWidgetData::class,
            'chart' => ChartWidgetData::class,
            'table', 'grid' => $hasProvider ? TableWidgetData::class : null,
            default => null,
        };

        if ($expected === null) {
            return;
        }

        if (! $payload instanceof $expected) {
            throw new WidgetRuntimeException(
                500,
                sprintf(
                    'Flatpack widget provider returned %s but %s was expected for widget type "%s".',
                    $payload::class,
                    $expected,
                    $type,
                ),
            );
        }
    }
}
