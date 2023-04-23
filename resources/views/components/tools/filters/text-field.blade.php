@php
    $theme = $component->getTheme();
    $filterLayout = $component->getFilterLayout();
    $tableName = $component->getTableName();
@endphp
<div>
    <x-input
        wire:model.stop="{{ $tableName }}.filters.{{ $filter->getKey() }}"
        wire:key="{{ $tableName }}-filter-{{ $filter->getKey() }}"
        id="{{ $tableName }}-filter-{{ $filter->getKey() }}"
        :placeholder="$filter->getConfig('placeholder')"
        :maxlength="$filter->getConfig('maxlength')"
        :label="$filter->getName()"
    />
</div>
