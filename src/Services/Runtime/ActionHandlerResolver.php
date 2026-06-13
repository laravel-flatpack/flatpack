<?php

declare(strict_types=1);

namespace Flatpack\Services\Runtime;

use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Contracts\Actions\FlatpackBulkAction;
use Flatpack\Support\Exceptions\ActionRuntimeException;

final class ActionHandlerResolver
{
    public function resolveRecordActionHandler(string $action): FlatpackAction
    {
        $handlerClass = config("flatpack.actions.{$action}");
        if (! is_string($handlerClass) || $handlerClass === '') {
            throw new ActionRuntimeException(
                404,
                sprintf('Flatpack action "%s" is not configured. Add it to config/flatpack.php under "actions".', $action),
            );
        }

        $handler = app()->make($handlerClass);
        if (! $handler instanceof FlatpackAction) {
            throw new ActionRuntimeException(500, 'Flatpack action handler must implement FlatpackAction.');
        }

        return $handler;
    }

    public function resolveBulkActionHandler(string $action): FlatpackBulkAction
    {
        $handlerClass = config("flatpack.bulk_actions.{$action}");
        if (! is_string($handlerClass) || $handlerClass === '') {
            throw new ActionRuntimeException(
                404,
                sprintf('Flatpack bulk action "%s" is not configured. Add it to config/flatpack.php under "bulk_actions".', $action),
            );
        }

        $handler = app()->make($handlerClass);
        if (! $handler instanceof FlatpackBulkAction) {
            throw new ActionRuntimeException(500, 'Flatpack bulk action handler must implement FlatpackBulkAction.');
        }

        return $handler;
    }
}
