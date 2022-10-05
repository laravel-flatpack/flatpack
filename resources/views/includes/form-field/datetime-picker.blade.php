@if (strtolower($type ?? '') === 'datetime-picker')
<x-datetime-picker
    without-timezone
    :label="$label"
    :placeholder="$placeholder"
	:disabled="$disabled"
    :readonly="$readonly"
    :min="data_get($options, 'min', false)"
	:max="data_get($options, 'max', false)"
    :parse-format="data_get($options, 'format.parse', 'YYYY-MM-DD HH:mm:ss')"
    wire:model.defer="fields.{{ $key }}"
/>
@endif