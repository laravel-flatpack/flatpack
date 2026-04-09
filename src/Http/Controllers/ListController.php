<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Composition\EntityComposition;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ListController
{
    public function __construct(
        private EntityComposition $entityComposition,
    ) {}

    public function index(string $entity): Response
    {
        $list = $this->entityComposition->listFor($entity);

        return Inertia::render('list', [
            'entity' => $entity,
            'name' => $list->name,
            'model' => $list->model,
            'icon' => $list->icon,
        ]);
    }
}
