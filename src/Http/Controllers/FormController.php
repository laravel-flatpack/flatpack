<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Composition\EntityComposition;
use Flatpack\Http\Responses\FlatpackResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final readonly class FormController
{
    public function __construct(
        private EntityComposition $entityComposition,
    ) {}

    public function create(Request $request, string $entity): Response|JsonResponse
    {
        $form = $this->entityComposition->formFor($entity);
        $schema = $this->entityComposition->formSchema($entity);

        return FlatpackResponse::inertia('form', [
            'entity' => $entity,
            'name' => $form->name,
            'model' => $form->model,
            'icon' => $form->icon,
            'record' => null,
            'mode' => 'create',
            'schema' => $schema,
        ], $request->boolean('json'));
    }

    public function edit(Request $request, string $entity, string $record): Response|JsonResponse
    {
        $form = $this->entityComposition->formFor($entity);
        $schema = $this->entityComposition->formSchema($entity);

        return FlatpackResponse::inertia('form', [
            'entity' => $entity,
            'name' => $form->name,
            'model' => $form->model,
            'icon' => $form->icon,
            'record' => $record,
            'mode' => 'edit',
            'schema' => $schema,
        ], $request->boolean('json'));
    }
}
