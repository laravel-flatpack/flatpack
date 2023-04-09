@aware(['component'])
@props(['column' => null, 'customAttributes' => []])

@php
    $theme = $component->getTheme();
@endphp

<td {{ $attributes
    ->merge($customAttributes)
    ->class(['px-6 py-4 whitespace-nowrap text-sm font-medium dark:text-white' => $customAttributes['default'] ?? true])
    ->class(['hidden sm:table-cell' => $column && $column->shouldCollapseOnMobile()])
    ->class(['hidden md:table-cell' => $column && $column->shouldCollapseOnTablet()])
    ->except('default')
}}>{{ $slot }}</td>
