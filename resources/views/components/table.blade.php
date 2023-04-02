<div class="flex flex-col w-full gap-0">
    <div class="flex flex-row items-center justify-between w-full my-5">
        <h1 class="text-3xl font-bold">{{ Str::ucfirst($entity) }}</h1>
        <div class="flex flex-row items-center justify-end gap-1">
            @include('flatpack::includes.toolbar', [
                'elements' => $toolbar,
                'entity' => $entity,
                'model' => $model,
            ])
        </div>
    </div>
    
    {{--@include('flatpack::includes.table.scopes')--}}
    
    <div>
        <div
            @if (is_numeric($refresh))
                wire:poll.{{ $refresh }}ms
            @elseif(is_string($refresh))
                @if ($refresh === '.keep-alive' || $refresh === 'keep-alive')
                    wire:poll.keep-alive
                @elseif($refresh === '.visible' || $refresh === 'visible')
                    wire:poll.visible
                @else
                    wire:poll="{{ $refresh }}"
                @endif
            @endif
        >
            @include('livewire-tables::includes.debug')
            @include('livewire-tables::tailwind.includes.offline')

            <div class="flex-col">
                @include('livewire-tables::tailwind.includes.sorting-pills')
                @include('livewire-tables::tailwind.includes.filter-pills')

                <div class="space-y-5">
                    <div class="px-6 py-0 md:flex md:justify-between md:p-0">
                        <div class="w-full mb-4 space-y-4 md:mb-0 md:w-2/4 md:flex md:space-y-0 md:space-x-2">
                            @include('flatpack::includes.table.search')
                            @include('flatpack::includes.table.reorder')
                            @include('flatpack::includes.table.filters')
                        </div>

                        <div class="md:flex md:items-center">
                            @include('flatpack::includes.table.bulk-actions')
                            @include('flatpack::includes.table.column-select')
                            @include('flatpack::includes.table.per-page')
                        </div>
                    </div>

                    @include('flatpack::includes.table.table')
                    @include('flatpack::includes.table.pagination')
                </div>
            </div>
        </div>

        @isset($modalsView)
            @include($modalsView)
        @endisset
    </div>
</div>
