@if (in_array(strtolower($type), ['relation','select']))
<div class="w-full mt-1 relative rounded-md shadow-sm">
    <select
        wire:model.stop="fields.{{ $key }}"
        wire:key="fields-{{ $key }}"
        id="fields-{{ $key }}"
        class="form-select block w-full border-gray-300 rounded-md shadow-sm transition duration-150 ease-in-out focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
        {{ $disabled ? 'disabled' : '' }}
        {{ $required ? 'required' : '' }}
        {{ $readonly ? 'readonly' : '' }}
    >
        <option>Select an option</option>

        @foreach($items as $optionValue => $display)

        <option value="{{ $optionValue }}">
            {{ $display }}
        </option>

        @endforeach
    </select>
</div>
@endif
