<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Composition\EntityComposition;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FormController
{
    public function __construct(
        private EntityComposition $entityComposition,
    ) {}

    public function create(string $entity): Response
    {
        $form = $this->entityComposition->formFor($entity);

        return Inertia::render('form', [
            'entity' => $entity,
            'name' => $form->name,
            'model' => $form->model,
            'icon' => $form->icon,
            'record' => null,
            'mode' => 'create',
        ]);
    }

    public function edit(string $entity, string $record): Response
    {
        return Inertia::render('form', [
            'entity' => $entity,
            'record' => $record,
            'mode' => 'edit',
        ]);
    }
}
