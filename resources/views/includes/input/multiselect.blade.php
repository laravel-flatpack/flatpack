@if (in_array(strtolower($type), ['relation','multiselect']))
<div class="w-full">
    <ul class="w-full py-1 overflow-y-scroll list-none bg-gray-200 rounded-sm shadow-inner max-h-60">
        @foreach ($items as $optionValue => $display)
        <li class="mx-3 my-2">
            <label class="flex items-start justify-start cursor-pointer">
                <input
                    type="checkbox"
                    wire:model="fields.{{ $key }}"
                    class="mt-1 text-indigo-600 border-gray-300 rounded shadow-sm focus:border-indigo-300 focus:ring focus:ring-offset-0 focus:ring-indigo-200 focus:ring-opacity-50"
                    value="{{ $optionValue }}"
                    />
                <span class="w-full ml-3 overflow-hidden text-ellipsis whitespace-nowrap">{{ $display }}</span>
            </label>
        </li>
        @endforeach
    </ul>
    @if ($canCreate)
    <div class="mt-4" x-data="{ visible: false }" x-on:close-modal.window="visible = false">
        <div x-show="visible === true" x-cloak>
            <x-flatpack-modal title="Create new {{ Str::singular($key) }}">
                <livewire:flatpack.create-relation
                    :key="$key.'_create'"
                    :composition="['fields' => $formFields]"
                    :model="$relationshipModel"
                />
            </x-flatpack-modal>
        </div>
        <button
            @click="visible = !visible"
            class="text-black bg-gray-100 button">
            <span class="whitespace-nowrap">{{ __("Create new :entity", ["entity" => Str::singular($key)]) }}</span>
        </button>
    </div>
    @endif
</div>
@endif
