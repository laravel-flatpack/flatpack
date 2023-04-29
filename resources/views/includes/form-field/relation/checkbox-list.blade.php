@if ($type === 'relation')
<div class="w-full">
    <ul class="w-full py-1 overflow-y-scroll list-none bg-gray-200 rounded-sm shadow-inner max-h-60">
        @foreach ($items as $optionValue => $display)
        <li class="mx-3 my-2">
            <label class="flex items-center justify-start cursor-pointer">
                <input
                    type="checkbox"
                    wire:model="{{ $binding }}.{{ $key }}"
                    class="border-gray-300 rounded shadow-sm text-primary-300 focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                    value="{{ $optionValue }}"
                    />
                <span class="w-full ml-3 overflow-hidden text-base sm:text-sm text-ellipsis whitespace-nowrap">{{ $display }}</span>
            </label>
        </li>
        @endforeach
    </ul>
    @if ($canCreate ?? false)
    <div class="mt-4" x-data="{ visible: false, toggle() { this.visible = ! this.visible } }" x-on:close-modal.window="toggle()">
        <div x-show="visible" x-cloak x-ref="modal">
            <x-flatpack-modal title="Create new {{ Str::singular($key) }}">
                <livewire:flatpack.create-relation
                    :formFields="$formFields"
                    :model="$relationshipModel"
                />
            </x-flatpack-modal>
        </div>
        <x-button
            wire:key="toggle-modal-{{ $key }}"
            label="Create new {{ Str::singular($key) }}"
            @click="toggle(); setTimeout(() => $refs.modal.querySelector('input').focus(), 200);"
        />
    </div>
    @endif
</div>
@endif
