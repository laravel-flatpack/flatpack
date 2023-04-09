@aware(['component'])
@props(['row'])

@if ($component->bulkActionsAreEnabled() && $component->hasBulkActions())
    @php
        $theme = $component->getTheme();
    @endphp

    <x-livewire-tables::table.td.plain>
        <div class="inline-flex rounded-md shadow-sm">
            <x-checkbox 
                wire:model="selected"
                wire:loading.attr.delay="disabled"
                value="{{ $row->{$this->getPrimaryKey()} }}"
            />
        </div>
    </x-livewire-tables::table.td.plain>
@endif
