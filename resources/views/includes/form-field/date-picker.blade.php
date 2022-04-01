@if (strtolower($type ?? '') === 'date-picker')
<x-datetime-picker
    without-timezone
    without-time
    :label="$label"
    :placeholder="$placeholder"
    :disabled="$disabled"
    :readonly="$readonly"
    :min="data_get($options, 'min', false)"
    :max="data_get($options, 'max', false)"
    wire:model.defer="fields.{{ $key }}"
/>
@endif
