<?php

namespace Flatpack\Http\Livewire\Filters;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class MultiSelectFilter extends Filter
{
    protected array $options = [];

    public function options(array $options = []): MultiSelectFilter
    {
        $this->options = $options;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getKeys(): array
    {
        return collect($this->getOptions())
            ->keys()
            ->map(fn ($value) => (string)$value)
            ->filter(fn ($value) => strlen($value))
            ->values()
            ->toArray();
    }

    public function validate($value)
    {
        if (is_array($value)) {
            foreach ($value as $index => $val) {
                // Remove the bad value
                if (! in_array($val, $this->getKeys())) {
                    unset($value[$index]);
                }
            }
        }

        return $value;
    }

    /**
     * Get the filter default options.
     *
     * @return array<mixed>
     */
    public function getDefaultValue()
    {
        return [];
    }

    public function getFilterPillValue($value): ?string
    {
        $values = [];

        foreach ($value as $item) {
            $found = $this->getCustomFilterPillValue($item) ?? $this->getOptions()[$item] ?? null;

            if ($found) {
                $values[] = $found;
            }
        }

        $values = Arr::map($values, function (string $value, string $key) {
            return Str::limit($value, 8);
        });

        if (count($values) < 3) {
            return implode(', ', $values);
        }

        return implode(', ', array_slice($values, 0, 2)) . ' ' .
            __('+:count more', ['count' => count($values)]);
    }

    public function isEmpty($value): bool
    {
        return ! is_array($value);
    }

    public function render(DataTableComponent $component)
    {
        return view('flatpack::components.tools.filters.multi-select', [
            'component' => $component,
            'filter' => $this,
        ]);
    }
}
