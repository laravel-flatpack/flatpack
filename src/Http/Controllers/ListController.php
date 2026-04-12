<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Composition\EntityComposition;
use Flatpack\Http\FlatpackResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final readonly class ListController
{
    public function __construct(
        private EntityComposition $entityComposition,
    ) {}

    public function index(Request $request, string $entity): Response|JsonResponse
    {
        $list = $this->entityComposition->listFor($entity);
        $schema = $this->entityComposition->listSchema($entity);

        return FlatpackResponse::inertia('list', [
            'entity' => $entity,
            'name' => $list->name,
            'model' => $list->model,
            'icon' => $list->icon,
            'order' => $list->order,
            'schema' => $schema,
        ], $request->boolean('json'));
    }
}
