<?php

declare(strict_types=1);

namespace Flatpack\Services\Runtime;

use Flatpack\Contracts\Widgets\WidgetDataProvider;
use Flatpack\Support\Exceptions\WidgetRuntimeException;
use Flatpack\Widgets\WidgetDataContext;
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
    public function resolveData(WidgetDataProvider $provider, WidgetDataContext $context): array
    {
        $user = $context->request->user();
        if ($user === null) {
            throw new AuthorizationException('This widget is not authorized.');
        }

        if (! $provider->authorize($user, $context)) {
            throw new AuthorizationException('This widget is not authorized.');
        }

        return $provider->handle($context);
    }
}
