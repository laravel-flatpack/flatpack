@aware(['component'])
@props(['rows'])

@if ($component->bulkActionsAreEnabled() && $component->hasBulkActions() && $component->hasSelected())
    @php
        $table = $component->getTableName();
        $theme = $component->getTheme();
        $colspan = $component->getColspanCount();
        $selected = $component->getSelectedCount();
        $selectAll = $component->selectAllIsEnabled();
        $simplePagination = ($component->paginationMethod == "simple") ? true : false;
    @endphp

    <tr 
        wire:key="bulk-select-message-{{ $table }}"
        class="bg-primary-50 bg-opacity-30 dark:bg-gray-900 dark:text-white"
    >
        <x-flatpack::table.td.plain :colspan="$colspan">
            @if ($selectAll)
                <div wire:key="all-selected-{{ $table }}">
                    <span>
                        @lang('You are currently selecting all')
                        @if(!$simplePagination) <strong>{{ number_format($rows->total()) }}</strong> @endif
                        @lang('rows').
                    </span>

                    <button
                        wire:click="clearSelected"
                        wire:loading.attr="disabled"
                        type="button"
                        class="ml-1 underline text-gray-700 text-sm leading-5 font-medium focus:outline-none focus:text-gray-800 focus:underline transition duration-150 ease-in-out dark:text-white dark:hover:text-gray-400"
                    >
                        @lang('Deselect All')
                    </button>
                </div>
            @else
                <div wire:key="some-selected-{{ $table }}">
                    <span>
                        @lang('You have selected')
                        <strong>{{ $selected }}</strong>
                        @lang('rows, do you want to select all')
                        @if(!$simplePagination) <strong>{{ number_format($rows->total()) }}</strong> @endif
                        ?
                    </span>

                    <button
                        wire:click="setAllSelected"
                        wire:loading.attr="disabled"
                        type="button"
                        class="ml-1 underline text-gray-700 hover:text-gray-800 text-sm leading-5 font-medium focus:outline-none focus:text-gray-800 focus:underline transition duration-150 ease-in-out dark:text-white dark:hover:text-gray-400"
                    >
                        @lang('Select All')
                    </button>

                    <button
                        wire:click="clearSelected"
                        wire:loading.attr="disabled"
                        type="button"
                        class="ml-1 underline text-gray-700 hover:text-gray-800 text-sm leading-5 font-medium focus:outline-none focus:text-gray-800 focus:underline transition duration-150 ease-in-out dark:text-white dark:hover:text-gray-400"
                    >
                        @lang('Deselect All')
                    </button>
                </div>
            @endif
        </x-flatpack::table.td.plain>
    </tr>

@endif
