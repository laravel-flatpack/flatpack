@if (strtolower($type ?? '') === 'text')
<x-input
    :label="$label"
    :placeholder="$placeholder"
    :disabled="$disabled"
    :readonly="$readonly"
    wire:model.defer="fields.{{ $key }}"
/>
@endif
