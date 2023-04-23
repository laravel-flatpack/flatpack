@php
    $theme = $component->getTheme();
    $filterLayout = $component->getFilterLayout();
    $tableName = $component->getTableName();
@endphp
<div>
    <x-select
        wire:model.stop="{{ $tableName }}.filters.{{ $filter->getKey() }}"
        wire:key="{{ $tableName }}-filter-{{ $filter->getKey() }}" 
        id="{{ $tableName }}-filter-{{ $filter->getKey() }}"        
        label="{{ $filter->getName() }}"
        placeholder="Select a filter"
        multiselect
    >
        @foreach($filter->getOptions() as $key => $value)
            <x-select.option label="{{ $value }}" value="{{ $key }}" />
        @endforeach
    </x-select>
</div>
