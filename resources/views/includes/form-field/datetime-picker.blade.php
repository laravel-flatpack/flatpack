@if (strtolower($type ?? '') === 'datetime-picker')
<x-datetime-picker
    x-on:change.debounce="Flatpack.form.inputChange($event, '{{ $binding }}.{{ $key }}')"
    wire:model.defer="{{ $binding }}.{{ $key }}"
    wire:key="{{ $binding }}-{{ $key }}"
    :label="$label"
    :placeholder="$placeholder"
	:disabled="$disabled"
    :readonly="$readonly"
    :min="data_get($options, 'min', false)"
	:max="data_get($options, 'max', false)"
    :parse-format="data_get($options, 'format.parse', 'YYYY-MM-DD HH:mm:ss')"
    without-timezone
/>
@endif