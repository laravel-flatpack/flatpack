<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Http\Controllers\Concerns\AuthorizesModelAbility;
use Flatpack\Http\Controllers\Concerns\BuildsListPageProps;
use Flatpack\Http\Controllers\Concerns\LoadsListComposition;
use Flatpack\Http\Controllers\Concerns\LoadsListRecords;
use Flatpack\Http\Controllers\Concerns\ResolvesListQuery;
use Flatpack\Http\Controllers\Concerns\ResolvesWidgets;
use Flatpack\Http\FlatpackResponse;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Flatpack\Services\Lists\ActiveTabResolver;
use Flatpack\Services\Lists\ListRecordsLoader;
use Flatpack\Services\Runtime\WidgetRuntime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

final readonly class ListController
{
    use AuthorizesModelAbility;
    use BuildsListPageProps;
    use LoadsListComposition;
    use LoadsListRecords;
    use ResolvesListQuery;
    use ResolvesWidgets;

    public function __construct(
        private ActiveTabResolver $activeTabResolver,
        private WidgetSchemaNormalizer $widgetSchemaNormalizer,
        private WidgetRuntime $widgetRuntime,
        private ListRecordsLoader $listRecordsLoader,
    ) {}

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
        $resolvedTab = $this->activeTabResolver->resolveWithSchema($schema, $query['tab']);
        $activeTab = $resolvedTab->activeTab;
        $this->assertValidTabScope($modelClass, $activeTab);
        $effectiveSchema = $resolvedTab->effectiveSchema;
        $result = $this->loadRecordsForList(
            $modelClass,
            $effectiveSchema,
            $query,
            $resolvedTab->scope,
        );
        $debugContext = FlatpackResponse::compositionDebugContextForEntity($entity, 'list.yaml');
        $debugLog = FlatpackResponse::compositionDebugLog($debugContext);
        $widgetsSchema = $this->normalizedWidgetsSchema($effectiveSchema, $debugLog);
        $resolvedWidgets = $this->resolveWidgetDataWhenPresent(
            $request,
            $entity,
            $widgetsSchema['widgets'] ?? [],
            $debugLog,
        );

        return FlatpackResponse::inertia(
            'list',
            $this->listPageProps(
                $entity,
                $list,
                $effectiveSchema,
                $result,
                $query['searchTerm'],
                $activeTab['id'] ?? null,
                $debugLog,
                $resolvedWidgets,
                $widgetsSchema,
            ),
            compositionDebugLog: $debugLog,
        );
    }

    /**
     * @param  array{id: string, scope?: string, columns?: mixed}|null  $activeTab
     *
     * @throws ValidationException
     */
    private function assertValidTabScope(string $modelClass, ?array $activeTab): void
    {
        if ($activeTab === null) {
            return;
        }
        $scope = trim((string) ($activeTab['scope'] ?? ''));
        if ($scope === '') {
            return;
        }
        if ($modelClass === '' || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw ValidationException::withMessages([
                'flatpack' => sprintf(
                    'Tab "%s" references scope "%s", but list model "%s" is not a valid Eloquent model.',
                    $activeTab['id'],
                    $scope,
                    $modelClass,
                ),
            ]);
        }
        $scopeMethod = 'scope' . ucfirst($scope);
        if (! method_exists($modelClass, $scopeMethod)) {
            throw ValidationException::withMessages([
                'flatpack' => sprintf(
                    'Tab "%s" references missing scope "%s" on %s.',
                    $activeTab['id'],
                    $scope,
                    $modelClass,
                ),
            ]);
        }
    }

    private function widgetSchemaNormalizer(): WidgetSchemaNormalizer
    {
        return $this->widgetSchemaNormalizer;
    }

    private function widgetRuntime(): WidgetRuntime
    {
        return $this->widgetRuntime;
    }

    private function listRecordsLoader(): ListRecordsLoader
    {
        return $this->listRecordsLoader;
    }
}
