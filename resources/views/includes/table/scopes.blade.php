@if (sizeof($scopes ?? []) > 0)
<ul class="items-center justify-start hidden mb-2 -mt-8 list-none border-b border-gray-300 md:flex">
    @foreach ($scopes as $key => $options)
        <li class="pr-1">
            <button
                wire:click="$set('scope', '{{ $key }}')"
                style="min-width: 52px"
                class="flex items-center justify-center px-2 py-2 bg-transparent rounded-md">
                <span class="text-md cursor-pointer whitespace-nowrap {{ $key === $scope ? 'font-bold': '' }}">
                    {{ data_get($options, 'label', $key) }}
                </span>
                @if (data_get($options, 'count', false))
                    <span class="flex items-center justify-center px-2 ml-2 text-xs text-white bg-gray-400 rounded-lg py-0.5">{{ $this->scopeQuery($key)->count() }}</span>
                @endif
            </button>
        </li>
    @endforeach
</ul>
<div class="block w-full px-6 mb-2 md:hidden">
    <div
        x-data="{ open: false }"
        @keydown.window.escape="open = false"
        x-on:click.away="open = false"
        class="relative inline-block w-full text-left md:w-auto"
    >
        <div>
            <span class="rounded-md shadow-sm">
                <button
                    x-on:click="open = !open"
                    type="button"
                    class="inline-flex justify-center w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600"
                    aria-haspopup="true"
                    x-bind:aria-expanded="open"
                    aria-expanded="true"
                >
                    {{ $scopes[$scope]['label'] ?? $scope }}

                    <svg class="w-5 h-5 ml-2 -mr-1" x-description="Heroicon name: chevron-down" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
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
            class="absolute right-0 z-50 w-full mt-2 origin-top-right bg-white divide-y divide-gray-100 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 md:w-48 focus:outline-none"
        >
            <div class="bg-white rounded-md shadow-xs dark:bg-gray-700 dark:text-white">
                <div class="p-2" role="menu" aria-orientation="vertical">
                    @foreach ($scopes as $key => $options)
                        <div wire:key="scopeSelect-{{ $loop->index }}">
                            <button
                                wire:loading.attr="disabled"
                                wire:click="$set('scope', '{{ $key }}')"
                                x-on:click="open = !open"
                                class="flex items-center w-full px-4 py-2 space-x-2 text-sm leading-5 text-gray-700 rounded-sm hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900 dark:text-white dark:hover:bg-gray-600"
                                type="button"
                                role="menuitem"
                            >
                                <span class="ml-2">{{ data_get($options, 'label', $key) }}</span>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif
