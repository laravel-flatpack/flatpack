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
            'catalog' => $this->when(! empty($catalog), $catalog),
            'records' => $this->when(
                array_key_exists('records', $resource),
                data_get($resource, 'records'),
            ),
        ];
    }
}
