<div class="w-full">
<ul class="list-none w-full max-h-80 overflow-y-scroll bg-gray-200">
    @foreach ($items as $optionValue => $display)
    <li class="my-2">
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
    <button
        class="button bg-gray-100 text-black">
        <span class="whitespace-nowrap">Create new {{ Str::singular($key) }}</span>
    </button>
</div>
@endif
</div>
