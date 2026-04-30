<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Http\FlatpackResponse;
use Flatpack\Services\Demo\DemoCatalogFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final readonly class DemoController
{
    public function __construct(
        private DemoCatalogFactory $demoCatalog,
    ) {}

    public function index(Request $request): Response|JsonResponse
    {
        $catalogId = $this->demoCatalog->normalizeCatalogId($request->query('catalog'));

        return FlatpackResponse::inertia('docs/catalog', [
            'catalogId' => $catalogId,
            'query' => $request->query(),
            'document' => $this->demoCatalog->buildDocument($catalogId),
        ]);
    }
}
