@aware(['component', 'row', 'rowIndex'])
@props(['column', 'colIndex'])

@php
    $attributes = $attributes->merge(['wire:key' => 'cell-'.$rowIndex.'-'.$colIndex.'-'.$component->id]);
    $theme = $component->getTheme();
    $customAttributes = $component->getTdAttributes($column, $row, $colIndex, $rowIndex)
@endphp

<td
    @if ($column->isClickable())
        onclick="window.open('{{ $component->getTableRowUrl($row) }}', '{{ $component->getTableRowUrlTarget($row) ?? '_self' }}')"
    @endif

    {{
        $attributes->merge($customAttributes)
            ->class(['px-6 py-4 whitespace-nowrap text-sm font-normal dark:text-white' => $customAttributes['default'] ?? true])
            ->class(['hidden sm:table-cell' => $column && $column->shouldCollapseOnMobile()])
            ->class(['hidden md:table-cell' => $column && $column->shouldCollapseOnTablet()])
            ->except('default')
    }}
>
    {{ $slot }}
</td>
