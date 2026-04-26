<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Composition\EntityComposition;
use Flatpack\Facades\Flatpack;
use Flatpack\Http\Controllers\Concerns\AuthorizesFlatpackModelAbility;
use Flatpack\Http\FlatpackResponse;
use Flatpack\Schema\HeaderActions;
use Flatpack\Schema\Lists\BulkActions;
use Flatpack\Schema\Lists\ListRecordsLoader;
use Flatpack\Support\ModelKeyResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final readonly class ListController
{
    use AuthorizesFlatpackModelAbility;

    public function __construct(
        private EntityComposition $entityComposition,
        private ListRecordsLoader $listRecords,
        private ModelKeyResolver $modelKeyResolver,
    ) {}

    /**
     * Display the entity list with pagination, search, filters, and sorting.
     */
    public function index(Request $request, string $entity): Response|JsonResponse
    {
        $list = $this->entityComposition->listFor($entity);
        $schema = $this->entityComposition->listSchema($entity);
        $this->ensureModelAbility($request, (string) ($list->model ?? ''), 'viewAny');

        $page = max(1, (int) $request->query('page', 1));
        $maxPerPage = Flatpack::maxListPerPage();
        $perPage = (int) $request->query(
            'per_page',
            Flatpack::defaultListPerPage(),
        );
        $perPage = max(1, min($maxPerPage, $perPage));
        $searchTerm = trim((string) $request->query('search', ''));
        $filters = $request->query('filters', []);
        $filters = is_array($filters) ? $filters : [];
        $sortBy = trim((string) $request->query('sort_by', ''));
        $sortDirection = mb_strtolower(trim((string) $request->query('sort_direction', '')));
        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $result = $this->listRecords->load(
            $list->model,
            $schema,
            $page,
            $perPage,
            $searchTerm,
            $filters,
            $sortBy,
            $sortDirection,
        );

        return FlatpackResponse::inertia('list', [
            'entity' => $entity,
            'name' => $list->name,
            'model' => $list->model,
            'model_key' => $this->modelKeyResolver->resolve($list->model),
            'icon' => $list->icon,
            'nav_order' => $list->nav_order,
            'schema' => $schema,
            'records' => $result['records'],
            'pagination' => $result['pagination'],
            'search_term' => $searchTerm,
            'filters' => $result['filters'],
            'filter_values' => $result['filter_values'],
            'sorting' => $result['sorting'],
            'list_actions' => HeaderActions::fromSchema($schema),
            'bulk_actions' => BulkActions::fromSchema($schema),
        ]);
    }
}
