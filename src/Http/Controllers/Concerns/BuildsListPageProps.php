<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Composition\ListComposition;
use Flatpack\Schema\HeaderActions;
use Flatpack\Schema\Lists\BulkActions;
use Flatpack\Schema\Lists\NormalizedListSchema;
use Flatpack\Support\ModelKeyResolver;

/**
 * Shared list page props builder for Flatpack list responses.
 */
trait BuildsListPageProps
{
    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, array<string, mixed>>  $widgets
     * @param  array<string, mixed>|null  $widgetsSchema
     * @return array<string, mixed>
     */
    private function listPageProps(
        string $entity,
        ListComposition $list,
        ?NormalizedListSchema $schema,
        array $result,
        string $searchTerm,
        ?string $activeTabId = null,
        array $widgets = [],
        ?array $widgetsSchema = null,
    ): array {
        $schemaArray = $schema?->toArray();

        return [
            'entity' => $entity,
            'name' => $list->name,
            'model' => $list->model,
            'model_key' => $this->modelKeyResolver()->resolve($list->model),
            'icon' => $list->icon,
            'nav_order' => $list->nav_order,
            'schema' => $schemaArray,
            'records' => $result['records'],
            'pagination' => $result['pagination'],
            'search_term' => $searchTerm,
            'active_tab' => $activeTabId,
            'filters' => $result['filters'],
            'filter_values' => $result['filter_values'],
            'sorting' => $result['sorting'],
            'list_actions' => HeaderActions::fromSchema($schemaArray),
            'bulk_actions' => BulkActions::fromSchema($schemaArray),
            'widgets' => $widgets,
            'widgets_schema' => $widgetsSchema,
        ];
    }

    private function modelKeyResolver(): ModelKeyResolver
    {
        return app(ModelKeyResolver::class);
    }
}
