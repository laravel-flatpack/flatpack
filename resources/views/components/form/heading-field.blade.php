@props([
    'type' => 'heading',
    'size' => 'medium',
    'value' => null,
    'disabled' => false,
    'readonly' => false,
    'required' => false,
    'key',
    'label',
    'placeholder'
])
@php
    $sizes = [
        'small' => 'text-sm font-normal',
        'medium' => 'text-xl font-normal',
        'large' => 'text-4xl font-bold'
    ];
    $sizeClass = $sizes[$size] ?? 'text-xl';
@endphp
<div class="flex justify-start items-center w-full gap-4">
    @if (!empty($label))
        <label class="flex text-sm uppercase font-bold text-gray-500">{{ $label }}</label>
    @endif
    @if ($type === 'editable')
        <input
            wire:model.stop="fields.{{ $key }}"
            wire:key="fields-{{ $key }}"
            id="h-fields-{{ $key }}"
            type="text"
            placeholder="{{ $placeholder ?? '' }}"
            class="{{ $sizeClass }} w-full border-0 px-3 py-2 rounded-md transition duration-150 ease-in-out focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            {{ $disabled ? 'disabled' : '' }}
            {{ $required ? 'required' : '' }}
            {{ $readonly ? 'readonly' : '' }}
        />
    @else
        <div class="w-full {{ $sizeClass }} py-2 text-gray-800">{{ $value ?? '-' }}</div>
    @endif
</div>
