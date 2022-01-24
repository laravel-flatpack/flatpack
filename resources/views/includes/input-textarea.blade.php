@if (strtolower($type ?? '' ?? '') === 'textarea')
<div class="w-full flex rounded-md shadow-sm mt-1">
    <textarea
        wire:model.stop="fields.{{ $key }}"
        wire:key="fields-{{ $key }}"
        id="fields-{{ $key }}"
        type="text"
        placeholder="{{ $placeholder ?? '' }}"
        class="block w-full resize-none border-gray-300 rounded-md shadow-sm transition duration-150 ease-in-out focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
        {{ $disabled ? 'disabled' : '' }}
        {{ $required ? 'required' : '' }}
        {{ $readonly ? 'readonly' : '' }}
    ></textarea>
</div>
@endif
