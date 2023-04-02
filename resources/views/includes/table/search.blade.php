@if ($showSearch && count($searchableColumns) > 0)
    <div class="flex rounded-md shadow-sm">
        <x-input wire:model="filters.search" placeholder="{{ __('Search') }}" class="w-full">
            <x-slot name="append">
                @if (isset($filters['search']) && strlen($filters['search']))
                <span wire:click="$set('filters.search', null)" class="absolute inset-y-0 right-0 px-3 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </span>
                @endif
            </x-slot>
        </x-input>
    </div>
@endif
