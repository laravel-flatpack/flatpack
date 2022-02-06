@if (in_array(strtolower($type), ['relation','multiselect']))
<div class="w-full">
<ul class="list-none w-full max-h-60 overflow-y-scroll bg-gray-200 shadow-inner py-1">
    @foreach ($items as $optionValue => $display)
    <li class="mx-3 my-2">
        <label class="flex justify-start items-start cursor-pointer">
            <input
                type="checkbox"
                wire:model="fields.{{ $key }}"
                class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-offset-0 focus:ring-indigo-200 focus:ring-opacity-50"
                value="{{ $optionValue }}"
                />
            <span class="ml-3 w-full overflow-hidden text-ellipsis whitespace-nowrap">{{ $display }}</span>
        </label>
    </li>
    @endforeach
</ul>
@if ($canCreate)
<div class="mt-4">
    <div x-data="{ visible: false }">
        <div x-show="visible === true" x-cloak>
            @include('flatpack::includes.modal', [
                'title' => 'Create New ' . Str::singular($key),
                'button' => 'Create',
                'buttonClass' => 'bg-indigo-600 text-white',
                'buttonIcon' => 'plus',
                'modalId' => 'create-new-'. $key
            ])
        </div>
        <button
            @click="visible = !visible"
            class="button bg-gray-100 text-black">
            <span class="whitespace-nowrap">Create new {{ Str::singular($key) }}</span>
        </button>
    </div>
</div>
@endif
</div>
@endif
