<?php

namespace Flatpack\Traits;

use Flatpack\Http\Livewire\Filters\DateFilter;
use Flatpack\Http\Livewire\Filters\MultiSelectFilter;
use Flatpack\Http\Livewire\Filters\NumberFilter;
use Flatpack\Http\Livewire\Filters\SelectFilter;
use Flatpack\Http\Livewire\Filters\TextFilter;
use Illuminate\Database\Eloquent\Builder;

trait WithFilters
{
    /**
     * Table filters definition.
     *
     * @return array
     */
    public function filters(): array
    {
        $filters = [];

        foreach ($this->composition['filters'] ?? [] as $key => $options) {
            $filters[] = $this->createFilter($key, $options);
        }

        return $filters;
    }

    /**
     * Setup Filter options items for filters on relations.
     *
     * @return void
     */
    public function setFilterOptions(): void
    {
        foreach ($this->composition['filters'] ?? [] as $key => $options) {
            $type = data_get($options, 'type', 'default');

            if ('relation' === $type) {
                $idField = data_get($options, 'relation.id', 'id');
                $nameField = data_get($options, 'relation.nameField', 'name');
                $method = data_get($options, 'relation.name', data_get($options, 'relation', $key));
                $relation = (new $this->model())->{$method}()->getRelated();

                $this->composition['filters'][$key]['options'] =
                    $relation->orderBy($nameField, 'asc')
                        ->get()
                        ->keyBy($idField)
                        ->map(fn ($model) => $model->{$nameField})
                        ->toArray();
            }
        }
    }

    private function createFilter($key, $options)
    {
        $type = data_get($options, 'type', 'default');
        $label = data_get($options, 'label', $key);
        $items = data_get($options, 'options', []);

        $map = [
            'default' => TextFilter::make($label, $key)
                ->config($options)
                ->filter(function (Builder $builder, string $value) use ($key) {
                    $builder->where($key, $value);
                }),
            'select' => SelectFilter::make($label, $key)
                ->options($items)
                ->config($options)
                ->filter(function (Builder $builder, string $value) use ($key) {
                    $builder->where($key, $value);
                }),
            'multiselect' => MultiSelectFilter::make($label, $key)
                ->options($items)
                ->config($options)
                ->filter(function (Builder $builder, array $value) use ($key) {
                    $builder->whereIn($key, $value);
                }),
            'relation' => MultiSelectFilter::make($label, $key)
                ->options($items)
                ->config($options)
                ->filter(function (Builder $builder, array $values) use ($key) {
                    $builder->whereHas($key, fn ($query) => $query->whereIn('categories.id', $values));
                }),
            'date' => DateFilter::make($label, $key)
                ->config($options)
                ->filter(function (Builder $builder, string $value) use ($key) {
                    $builder->whereDate($key, '=', $value);
                }),
            'number' => NumberFilter::make($label, $key)
                ->config($options)
                ->filter(function (Builder $builder, string $value) use ($key) {
                    $builder->where($key, $value);
                }),
        ];

        return $map[$type] ?? $map['default'];
    }
}
