@php
    $theme = $component->getTheme();
    $filterLayout = $component->getFilterLayout();
    $tableName = $component->getTableName();
@endphp
<div>
    <div class="rounded-md shadow-sm">
        <x-datetime-picker
            id="{{ $tableName }}-filter-{{ $filter->getKey() }}"
            label="{{ $filter->getName() }}"
            placeholder="{{ __('Select a date') }}"
            wire:model.stop="{{ $tableName }}.filters.{{ $filter->getKey() }}"
            wire:key="{{ $tableName }}-filter-{{ $filter->getKey() }}"
            without-time
        />
    </div>
</div>
