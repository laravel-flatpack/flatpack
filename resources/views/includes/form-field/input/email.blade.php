@if (strtolower($type) === 'email')
<x-input
    :label="$label"
    :placeholder="$placeholder"
    :disabled="$disabled"
    :readonly="$readonly"
    wire:model.defer="fields.{{ $key }}"
    type="email"
/>
@endif
