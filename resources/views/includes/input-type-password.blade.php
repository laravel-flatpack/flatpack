@if (strtolower($type) === 'password')
<div class="w-full flex rounded-md shadow-sm mt-1">
    <input
        wire:model.stop="fields.{{ $key }}"
        wire:key="fields-{{ $key }}"
        id="fields-{{ $key }}"
        type="password"
        placeholder="{{ $placeholder ?? '' }}"
        class="block w-full border-gray-300 rounded-md shadow-sm transition duration-150 ease-in-out focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
        {{ $disabled ? 'disabled' : '' }}
        {{ $required ? 'required' : '' }}
        {{ $readonly ? 'readonly' : '' }}
    />
</div>
@endif
