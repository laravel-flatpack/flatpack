<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Composition\EntityComposition;
use Flatpack\Composition\ListComposition;

/**
 * Shared composition loaders for list controllers.
 */
trait LoadsListComposition
{
    private function loadList(string $entity): ListComposition
    {
        return $this->entityComposition()->listFor($entity);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadListSchema(string $entity): ?array
    {
        return $this->entityComposition()->listSchema($entity);
    }

    private function listModelClass(ListComposition $list): string
    {
        return (string) ($list->model ?? '');
    }

    private function entityComposition(): EntityComposition
    {
        return app(EntityComposition::class);
    }
}
