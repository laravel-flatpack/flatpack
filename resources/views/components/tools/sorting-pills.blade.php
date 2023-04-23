@aware(['component'])

@php
    $theme = $component->getTheme();
@endphp

<div>
    @if ($component->sortingPillsAreEnabled() && $component->hasSorts())
        <div class="mb-2 px-4 md:p-0">
            <div class="text-gray-500 dark:text-white font-bold uppercase text-2xs leading-4 inline-flex py-0.5">@lang('Sorting'):</div>

            @foreach($component->getSorts() as $columnSelectName => $direction)
                @php
                    $column = $component->getColumnBySelectName($columnSelectName);
                @endphp

                @continue(is_null($column))
                @continue($column->isHidden())
                @continue($this->columnSelectIsEnabled() && ! $this->columnSelectIsEnabledForColumn($column))

                <span
                    wire:key="sorting-pill-{{ $columnSelectName }}"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs leading-4 bg-indigo-100 text-indigo-800 capitalize dark:bg-indigo-200 dark:text-indigo-900"
                >
                    <span class="font-medium">{{ $column->getSortingPillTitle() }}:</span>
                    <span class="font-normal">{{ $column->getSortingPillDirection($component, $direction) }}</span>

                    <button
                        wire:click="clearSort('{{ $columnSelectName }}')"
                        type="button"
                        class="flex-shrink-0 ml-0.5 h-4 w-4 rounded-full inline-flex items-center justify-center text-indigo-400 hover:bg-indigo-200 hover:text-indigo-500 focus:outline-none focus:bg-indigo-500 focus:text-white"
                    >
                        <span class="sr-only">@lang('Remove sort option')</span>
                        <svg class="h-2 w-2" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                            <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" />
                        </svg>
                    </button>
                </span>
            @endforeach

            <button
                wire:click.prevent="clearSorts"
                class="focus:outline-none active:outline-none"
            >
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-normal bg-gray-100 text-gray-800 dark:bg-gray-200 dark:text-gray-900">
                    @lang('Clear')
                </span>
            </button>
        </div>
    @endif
</div>
