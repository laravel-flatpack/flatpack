<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Composition\EntityComposition;
use Flatpack\Http\FlatpackResponse;
use Flatpack\Lists\ListRecordsLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final readonly class ListController
{
    public function __construct(
        private EntityComposition $entityComposition,
        private ListRecordsLoader $listRecords,
    ) {}

    public function index(Request $request, string $entity): Response|JsonResponse
    {
        $list = $this->entityComposition->listFor($entity);
        $schema = $this->entityComposition->listSchema($entity);

        $records = $this->listRecords->load($list->model, $schema);

        return FlatpackResponse::inertia('list', [
            'entity' => $entity,
            'name' => $list->name,
            'model' => $list->model,
            'icon' => $list->icon,
            'order' => $list->order,
            'schema' => $schema,
            'records' => $records,
        ], $request->boolean('json'));
    }
}
