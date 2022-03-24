@if (strtolower($type) === 'password')
<x-input
    :label="$label"
    :placeholder="$placeholder"
    :disabled="$disabled"
    :readonly="$readonly"
    wire:model.defer="fields.{{ $key }}"
    type="password"
    autocomplete="new-password"
/>
@endif
