<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Composition\EntityComposition;
use Flatpack\Http\FlatpackResponse;
use Flatpack\Lists\ListHeaderActions;
use Flatpack\Lists\ListRecordsLoader;
use Flatpack\Support\ModelKeyResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final readonly class ListController
{
    public function __construct(
        private EntityComposition $entityComposition,
        private ListRecordsLoader $listRecords,
        private ModelKeyResolver $modelKeyResolver,
    ) {}

    public function index(Request $request, string $entity): Response|JsonResponse
    {
        $list = $this->entityComposition->listFor($entity);
        $schema = $this->entityComposition->listSchema($entity);

        $page = max(1, (int) $request->query('page', 1));
        $maxPerPage = (int) config('flatpack.list.max_per_page', 100);
        $perPage = (int) $request->query(
            'per_page',
            (int) config('flatpack.list.per_page', 10),
        );
        $perPage = max(1, min($maxPerPage, $perPage));
        $searchTerm = trim((string) $request->query('search', ''));
        $filters = $request->query('filters', []);
        $filters = is_array($filters) ? $filters : [];

        $result = $this->listRecords->load(
            $list->model,
            $schema,
            $page,
            $perPage,
            $searchTerm,
            $filters,
        );
        $flatpackPrefix = trim((string) config('flatpack.prefix', 'flatpack'), '/');

        return FlatpackResponse::inertia('list', [
            'entity' => $entity,
            'name' => $list->name,
            'model' => $list->model,
            'model_key' => $this->modelKeyResolver->resolve($list->model),
            'icon' => $list->icon,
            'order' => $list->order,
            'schema' => $schema,
            'records' => $result['records'],
            'pagination' => $result['pagination'],
            'search_term' => $searchTerm,
            'filters' => $result['filters'],
            'filter_values' => $result['filter_values'],
            'flatpack_prefix' => $flatpackPrefix,
            'list_actions' => ListHeaderActions::fromSchema($schema),
        ], $request->boolean('json'));
    }
}
