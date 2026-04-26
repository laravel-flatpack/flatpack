<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Http\Controllers\Concerns\AuthorizesModelAbility;
use Flatpack\Http\Controllers\Concerns\BuildsListPageProps;
use Flatpack\Http\Controllers\Concerns\LoadsListComposition;
use Flatpack\Http\Controllers\Concerns\LoadsListRecords;
use Flatpack\Http\Controllers\Concerns\ResolvesListQuery;
use Flatpack\Http\FlatpackResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final readonly class ListController
{
    use AuthorizesModelAbility;
    use BuildsListPageProps;
    use LoadsListComposition;
    use LoadsListRecords;
    use ResolvesListQuery;

    /**
     * Display the entity list with pagination, search, filters, and sorting.
     */
    public function index(Request $request, string $entity): Response|JsonResponse
    {
        $list = $this->loadList($entity);
        $schema = $this->loadListSchema($entity);
        $modelClass = $this->listModelClass($list);
        $this->ensureModelAbility($request, $modelClass, 'viewAny');
        $query = $this->listQueryFromRequest($request);
        $result = $this->loadRecordsForList($modelClass, $schema, $query);

        return FlatpackResponse::inertia('list', $this->listPageProps(
            $entity,
            $list,
            $schema,
            $result,
            $query['searchTerm'],
        ));
    }
}
