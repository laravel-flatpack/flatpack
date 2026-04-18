<?php

declare(strict_types=1);

namespace Flatpack\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read array|null $schema
 * @property-read array|null $catalog
 */
final class FlatpackSchema extends JsonResource
{
    public static $wrap = 'data';

    #[Override]
    public function toArray($request): array
    {
        $schema = data_get($this->resource, 'schema', []);
        $catalog = data_get($this->resource, 'catalog', []);

        $resource = is_array($this->resource) ? $this->resource : [];

        return [
            'schema' => $this->when(! empty($schema), $schema),
            'model_key' => $this->when(
                array_key_exists('model_key', $resource),
                data_get($resource, 'model_key'),
            ),
            /** List properties */
            'list_actions' => $this->when(
                array_key_exists('list_actions', $resource),
                data_get($resource, 'list_actions'),
            ),
            'bulk_actions' => $this->when(
                array_key_exists('bulk_actions', $resource),
                data_get($resource, 'bulk_actions'),
            ),
            'records' => $this->when(
                array_key_exists('records', $resource),
                data_get($resource, 'records'),
            ),
            'pagination' => $this->when(
                array_key_exists('pagination', $resource),
                data_get($resource, 'pagination'),
            ),
            'sorting' => $this->when(
                array_key_exists('sorting', $resource),
                data_get($resource, 'sorting'),
            ),
            'search_term' => $this->when(
                array_key_exists('search_term', $resource),
                data_get($resource, 'search_term'),
            ),
            'filters' => $this->when(
                array_key_exists('filters', $resource),
                data_get($resource, 'filters'),
            ),
            'filter_values' => $this->when(
                array_key_exists('filter_values', $resource),
                data_get($resource, 'filter_values'),
            ),
            /** Form properties */
            'form_actions' => $this->when(
                array_key_exists('form_actions', $resource),
                data_get($resource, 'form_actions'),
            ),
            'values' => $this->when(
                array_key_exists('values', $resource),
                data_get($resource, 'values'),
            ),
            'composition_debug' => $this->when(
                array_key_exists('composition_debug', $resource),
                data_get($resource, 'composition_debug', []),
            ),
            /** Demo catalog properties */
            'catalog' => $this->when(! empty($catalog), $catalog),
        ];
    }
}
