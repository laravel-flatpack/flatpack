@if (strtolower($type ?? '') === 'datetimepicker')

    <input
        wire:model.stop="fields.{{ $key }}"
        wire:key="fields-{{ $key }}"
        id="fields-{{ $key }}"
        type="datetime-local"
        @if(isset($options['min']) && strlen($options['min'])) min="{{ $options['min'] }}" @endif
        @if(isset($options['max']) && strlen($options['max'])) max="{{ $options['max'] }}" @endif
        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm transition duration-150 ease-in-out focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
        {{ $disabled ? 'disabled' : '' }}
        {{ $required ? 'required' : '' }}
        {{ $readonly ? 'readonly' : '' }}
    />

@endif
