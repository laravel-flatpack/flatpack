<ul class="list-none">
    @foreach ($items as $optionValue => $display)
    <li>
        <label class="inline-flex items-center">
            <input
                type="checkbox"
                wire:model="fields.{{ $key }}"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-offset-0 focus:ring-indigo-200 focus:ring-opacity-50"
                value="{{ $optionValue }}"
                />
            <span class="ml-2">{{ $display }}</span>
        </label>
    </li>
    @endforeach
</ul>
