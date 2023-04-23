@aware(['component'])
@props(['rowIndex', 'hidden' => false])

@if ($component->collapsingColumnsAreEnabled() && $component->hasCollapsedColumns())
    @php
        $theme = $component->getTheme();
    @endphp
    
    <td
        @if (! $hidden) x-data="{open:false}" @endif
        {{
            $attributes
                ->merge(['class' => 'p-3 table-cell text-center'])
                ->class([
                    'md:hidden' =>
                        (($component->shouldCollapseOnMobile() && $component->shouldCollapseOnTablet()) ||
                        ($component->shouldCollapseOnTablet() && ! $component->shouldCollapseOnMobile()))
                ])
                ->class(['sm:hidden' => $component->shouldCollapseOnMobile() && ! $component->shouldCollapseOnTablet()])
        }}
    >
        @if (! $hidden)
            <button
                x-on:click.prevent="$dispatch('toggle-row-content', {'row': {{ $rowIndex }}});open = !open"
            >
                <svg x-show="!open" xmlns="http://www.w3.org/2000/svg"  class="text-green-600 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>

                <svg x-cloak x-show="open" xmlns="http://www.w3.org/2000/svg" class="text-yellow-600 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
        @endif
    </td>
@endif
