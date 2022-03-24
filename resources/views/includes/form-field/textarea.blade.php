@if (strtolower($type ?? '') === 'textarea')
<x-textarea
    :label="$label"
    :placeholder="$placeholder"
    wire:model.defer="fields.{{ $key }}"
    wire:key="fields-{{ $key }}"
    id="fields-{{ $key }}"
    :disabled="$disabled"
    :readonly="$readonly"
/>
@endif
