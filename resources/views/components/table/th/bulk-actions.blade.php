@aware(['component'])

@if ($component->bulkActionsAreEnabled() && $component->hasBulkActions())
    @php
        $theme = $component->getTheme();
    @endphp

    <x-livewire-tables::table.th.plain>
        <div class="inline-flex rounded-md shadow-sm">
            <x-checkbox wire:model="selectAll" />
        </div>
    </x-livewire-tables::table.th.plain>
@endif
