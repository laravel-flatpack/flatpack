<?php

declare(strict_types=1);

namespace Flatpack\Actions;

use Flatpack\Actions\Handlers\DeleteRecordHandler;
use Flatpack\Actions\Handlers\SaveRecordHandler;
use Flatpack\Contracts\Actions\ActionResolver;
use Flatpack\Contracts\Actions\FlatpackAction;
use Illuminate\Contracts\Container\Container;

final readonly class DefaultActionResolver implements ActionResolver
{
    public function __construct(
        private Container $container,
    ) {}

    public function resolve(string $actionName, array $composition): ?FlatpackAction
    {
        $actions = $composition['actions'] ?? [];

        if (isset($actions[$actionName]['callback']) && is_string($actions[$actionName]['callback'])) {
            $class = $actions[$actionName]['callback'];

            return $this->container->make($class);
        }

        return match ($actionName) {
            'save' => $this->container->make(SaveRecordHandler::class),
            'delete' => $this->container->make(DeleteRecordHandler::class),
            default => null,
        };
    }
}
