<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Composition\EntityComposition;
use Flatpack\Composition\FormComposition;

/**
 * Shared composition loaders for form controllers.
 */
trait LoadsFormComposition
{
    private function loadForm(string $entity): FormComposition
    {
        return $this->entityComposition()->formFor($entity);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadSchema(string $entity): ?array
    {
        return $this->entityComposition()->formSchema($entity);
    }

    private function formModelClass(FormComposition $form): string
    {
        return (string) ($form->model ?? '');
    }

    private function entityComposition(): EntityComposition
    {
        return app(EntityComposition::class);
    }
}
