<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Demo\DemoCatalogFactory;
use Flatpack\Http\FlatpackResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Inertia demo/catalog page for Flatpack UI primitives.
 *
 * Field entries for {@code catalog=fields} (including repeater, table, …) are defined in
 * {@see DemoCatalogFactory::fieldsCatalog()}.
 */
final readonly class DemoController
{
    public function __construct(
        private DemoCatalogFactory $demoCatalog,
    ) {}

    public function index(Request $request): Response|JsonResponse
    {
        $catalogId = $this->demoCatalog->normalizeCatalogId($request->query('catalog'));

        return FlatpackResponse::inertia('demo/catalog', [
            'catalogId' => $catalogId,
            'query' => $request->query(),
            'document' => $this->demoCatalog->buildDocument($catalogId),
        ]);
    }
}
