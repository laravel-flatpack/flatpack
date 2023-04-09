@aware(['component'])
@props(['column', 'index'])

@php
    $attributes = $attributes->merge(['wire:key' => 'header-col-'.$index.'-'.$component->id]);
    $theme = $component->getTheme();
    $customAttributes = $component->getThAttributes($column);
    $customSortButtonAttributes = $component->getThSortButtonAttributes($column);
    $direction = $column->hasField() ? $component->getSort($column->getColumnSelectName()) : null;
@endphp

<th scope="col" {{
    $attributes->merge($customAttributes)
        ->class(['px-6 py-3 text-left text-xs font-medium whitespace-nowrap text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:text-gray-400' => $customAttributes['default'] ?? true])
        ->class(['hidden sm:table-cell' => $column->shouldCollapseOnMobile()])
        ->class(['hidden md:table-cell' => $column->shouldCollapseOnTablet()])
        ->except('default')
}}>
    @unless ($component->sortingIsEnabled() && $column->isSortable())
        {{ $column->getTitle() }}
    @else
        <button
            wire:click="sortBy('{{ $column->getColumnSelectName() }}')"
            {{ 
                $attributes->merge($customSortButtonAttributes)
                    ->class(['flex items-center space-x-1 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider group focus:outline-none dark:text-gray-400' => $customSortButtonAttributes['default'] ?? true])
                    ->except(['default', 'wire:key'])
            }}
        >
            <span>{{ $column->getTitle() }}</span>

            <span class="relative flex items-center">
                @if ($direction === 'asc')
                    <svg class="w-3 h-3 group-hover:opacity-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>

                    <svg class="w-3 h-3 opacity-0 group-hover:opacity-100 absolute" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                @elseif ($direction === 'desc')
                    <svg class="w-3 h-3 opacity-0 group-hover:opacity-100 absolute" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>

                    <svg class="w-3 h-3 group-hover:opacity-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                @else
                    <svg class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>
                @endif
            </span>
        </button>
    @endunless
</th>
