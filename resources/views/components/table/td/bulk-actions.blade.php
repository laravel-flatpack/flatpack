@aware(['component'])
@props(['row'])

@if ($component->bulkActionsAreEnabled() && $component->hasBulkActions())
    @php
        $theme = $component->getTheme();
    @endphp

    <x-livewire-tables::table.td.plain>
        <input 
            wire:model="selected"
            type="checkbox" 
            value="{{ $row->{$this->getPrimaryKey()} }}"
            class="form-checkbox rounded transition ease-in-out duration-100 
                    border-secondary-300 text-primary-600 focus:ring-primary-600 focus:border-primary-400
                    dark:border-secondary-500 dark:checked:border-secondary-600 dark:focus:ring-secondary-600
                    dark:focus:border-secondary-500 dark:bg-secondary-600 dark:text-secondary-600
                    dark:focus:ring-offset-secondary-800"
        />
    </x-livewire-tables::table.td.plain>
@endif
