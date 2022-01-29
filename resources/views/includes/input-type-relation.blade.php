@if (strtolower($type) === 'relation')
<div class="w-full mt-1 relative rounded-md shadow-sm">
    <select
        wire:key="fields-{{ $key }}"
        id="fields-{{ $key }}"
        class="form-select block w-full border-gray-300 rounded-md shadow-sm transition duration-150 ease-in-out focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
        {{ $disabled ? 'disabled' : '' }}
        {{ $required ? 'required' : '' }}
        {{ $readonly ? 'readonly' : '' }}
    >
        <option {{ $currentValue === null ? 'selected' : '' }}>Select an option</option>

        @foreach($items as $value => $label)

        <option value="{{ $value }}" {{ $currentValue == $value ? 'selected' : '' }}>
            {{ $label }}
        </option>

        @endforeach
    </select>
</div>
@endif
