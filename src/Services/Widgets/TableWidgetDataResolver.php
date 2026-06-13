<?php

declare(strict_types=1);

namespace Flatpack\Services\Widgets;

use Flatpack\Widgets\Data\TableWidgetData;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Stringable;

/**
 * Builds a snapshot {@see TableWidgetData} from common Laravel result shapes.
 *
 * Accepted sources:
 *
 * - list/array of associative rows, or a single associative row
 * - {@see Collection} of arrays or {@see Model} instances
 * - {@see Model} instances (projected via `Model::only($columnIds)` when `$columnIds` is provided, otherwise `toArray()`)
 * - {@see JsonResource} (single row) and {@see ResourceCollection} (multiple rows)
 *
 * Provider-backed table widgets are snapshots: pagination, sorting, and search are client-side concerns and
 * intentionally not part of the resolved payload.
 */
final class TableWidgetDataResolver
{
    /**
     * @param  list<string>|null  $columnIds  When provided, projects Model rows via `Model::only()`.
     */
    public function resolve(
        mixed $source,
        ?array $columnIds = null,
        ?Request $request = null,
    ): TableWidgetData {
        if ($source instanceof EloquentBuilder || $source instanceof QueryBuilder) {
            throw new InvalidArgumentException(
                'Flatpack TableWidgetDataResolver expects an array, Collection, Model, or JsonResource. Execute the query first (e.g. ->get()).',
            );
        }

        $request ??= $this->fallbackRequest();

        if ($source instanceof ResourceCollection) {
            return new TableWidgetData($this->mapAssociativeListToRows($source->toArray($request)));
        }

        if ($source instanceof JsonResource) {
            return $this->resolveJsonResource($source, $request);
        }

        if ($source instanceof Collection) {
            return new TableWidgetData($this->mapItemsToRows($source->all(), $columnIds));
        }

        if ($source instanceof Model) {
            return new TableWidgetData([$this->modelToRow($source, $columnIds)]);
        }

        if (is_array($source)) {
            return $this->resolveArray($source, $columnIds);
        }

        throw new InvalidArgumentException(sprintf(
            'Flatpack TableWidgetDataResolver cannot resolve source of type %s.',
            get_debug_type($source),
        ));
    }

    private function resolveJsonResource(JsonResource $resource, Request $request): TableWidgetData
    {
        $resolved = $resource->resolve($request);
        if (! is_array($resolved)) {
            throw new InvalidArgumentException('Flatpack TableWidgetDataResolver expected JsonResource::resolve() to return an array.');
        }

        if ($resolved === []) {
            return new TableWidgetData([]);
        }

        if (array_is_list($resolved)) {
            return new TableWidgetData($this->mapAssociativeListToRows($resolved));
        }

        return new TableWidgetData([$this->coerceRow($resolved)]);
    }

    /**
     * @param  array<int|string, mixed>  $source
     * @param  list<string>|null  $columnIds
     */
    private function resolveArray(array $source, ?array $columnIds): TableWidgetData
    {
        if ($source === []) {
            return new TableWidgetData([]);
        }

        if (! array_is_list($source)) {
            return new TableWidgetData([$this->coerceRow($source)]);
        }

        return new TableWidgetData($this->mapItemsToRows($source, $columnIds));
    }

    /**
     * @param  list<mixed>  $items
     * @param  list<string>|null  $columnIds
     * @return list<array<string, int|float|string|bool|null>>
     */
    private function mapItemsToRows(array $items, ?array $columnIds): array
    {
        $rows = [];
        foreach ($items as $item) {
            if ($item instanceof Model) {
                $rows[] = $this->modelToRow($item, $columnIds);

                continue;
            }
            if (is_array($item)) {
                $rows[] = $this->coerceRow($item);
            }
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, int|float|string|bool|null>>
     */
    private function mapAssociativeListToRows(array $items): array
    {
        $rows = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $rows[] = $this->coerceRow($item);
            }
        }

        return $rows;
    }

    /**
     * @param  list<string>|null  $columnIds
     * @return array<string, int|float|string|bool|null>
     */
    private function modelToRow(Model $model, ?array $columnIds): array
    {
        $row = $columnIds === null ? $model->toArray() : $model->only($columnIds);

        return $this->coerceRow($row);
    }

    /**
     * @param  array<int|string, mixed>  $row
     * @return array<string, int|float|string|bool|null>
     */
    private function coerceRow(array $row): array
    {
        $out = [];
        foreach ($row as $key => $value) {
            $out[(string) $key] = $this->coerceCell($value);
        }

        return $out;
    }

    private function coerceCell(mixed $value): int|float|string|bool|null
    {
        if ($value === null || is_int($value) || is_float($value) || is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string) $value;
    }

    private function fallbackRequest(): Request
    {
        $resolved = request();

        return $resolved instanceof Request ? $resolved : Request::create('/');
    }
}
