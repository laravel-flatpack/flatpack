<div class="flex flex-row items-center justify-center gap-2">
@if ($scope === 'onlyTrashed')
    <x-flatpack-action-button
        key="action-empty-trash"
        :options="[
            'action' => 'empty-trash',
            'confirm' => true,
            'label' => __('Empty Trash'),
            'style' => 'danger',
            'size' => 'small'
        ]"
        :entity="$entity"
        :model="$model"
    />
@endif
@if ($this->showBulkActionsDropdown && (
    ($paginationEnabled && (($selectPage && $rows->total() > $rows->count()) ||
    count($selected))) ||
    count($selected)
))
    @if ($scope === 'onlyTrashed')
        <x-flatpack-action-button
            method="bulkAction"
            key="bulk-action-restore"
            :options="['action'=>'restore', 'confirm' => true, 'label' => __('Restore')]"
            :entity="$entity"
            :model="$model"
        />
    @else
        <div class="w-full mb-4 md:w-auto md:mb-0">
            <div
                x-data="{ open: false }"
                @keydown.window.escape="open = false"
                x-on:click.away="open = false"
                class="relative z-10 inline-block w-full text-left md:w-auto"
            >
                <div>
                    <span class="rounded-md shadow-sm">
                        <button
                            x-on:click="open = !open"
                            type="button"
                            class="inline-flex justify-center w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600"
                            id="options-menu"
                            aria-haspopup="true"
                            x-bind:aria-expanded="open"
                            aria-expanded="true"
                        >
                            @lang('Bulk Actions')

                            <svg class="w-5 h-5 ml-2 -mr-1" x-description="chevron-down" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </span>
                </div>

                <div
                    x-cloak
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 z-50 w-full mt-2 origin-top-right bg-white divide-y divide-gray-100 rounded-md shadow-lg md:w-48 ring-1 ring-black ring-opacity-5 focus:outline-none"
                >
                    <div class="bg-white rounded-md shadow-xs dark:bg-gray-700 dark:text-white">
                        <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                            @foreach($this->composition['bulk'] ?? [] as $key => $options)
                                <x-flatpack-action-button
                                    method="bulkAction"
                                    key="bulk-action-{{ $key }}"
                                    :options="$options"
                                    :entity="$entity"
                                    :model="$model"
                                />
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif
</div>
