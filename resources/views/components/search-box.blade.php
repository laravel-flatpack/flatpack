<div class="hidden md:flex flex-1 lg:justify-start relative">
    <div class="max-w-lg w-full lg:max-w-md">
        <label for="search" class="sr-only">Search</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <x-icon name="search" style="outline" class="w-4 h-4 text-gray-300" />
            </div>
            <input 
                class="block w-full pl-10 pr-3 py-2.5 border rounded-md leading-5 text-gray-200 border-gray-800 bg-gray-800 placeholder-gray-400 focus:outline-none focus:border-gray-800 focus:text-gray-100 sm:text-sm transition"
                autocomplete="off"
                placeholder="{{ __('Search...') }}"
                id="search"
                wire:model="search"
                wire:click="reset"
                wire:keydown.escape="hideDropdown"
                wire:keydown.tab="hideDropdown"
                wire:keydown.Arrow-Up="decrementHighlight"
                wire:keydown.Arrow-Down="incrementHighlight"
                wire:keydown.enter.prevent="selectResult"
            >
            <input type="hidden" name="selected" id="selected" wire:model="selectedResult">
        </div>
    </div>

    @if(!empty($search) && $selectedResult == 0 && $showDropdown)
        <div class="absolute z-10 bg-white mt-12 w-full border border-gray-300 rounded-md shadow-lg overflow-clip">
            @if (!empty($results))
                @foreach($results as $i => $result)
                    <a
                        wire:click="selectResult({{ $i }})"
                        class="flex justify-start items-start gap-2 py-2 px-3 text-sm cursor-pointer text-primary-400 hover:bg-secondary-50 {{ $highlightIndex === $i ? 'bg-secondary-50' : '' }}"
                    >
                        <div class="flex flex-col p-2">
                            @if ($result['icon'])
                                <x-icon name="{{ $result['icon'] }}" class="w-5 h-5 text-primary-100" />
                            @else
                                <x-icon name="document-search" class="w-5 h-5 text-primary-100" />
                            @endif
                        </div>
                        <div class="flex flex-col">
                            <span class="text-base font-medium">{{ $result['display'] }}</span>
                            <span class="text-xs">
                                {{ __('in') }} 
                                <span class="font-medium">{{ ucfirst($result['entity']) }}</span>
                            </span>
                        </div>
                    </a>
                @endforeach
            @else
                <span class="block py-2 px-3 text-sm  text-primary-200">No results!</span>
            @endif
        </div>
    @endif
</div>