@if (strtolower($type ?? '') === 'toggle')
<x-toggle
    md
    positive
    :label="$label"
    :disabled="$disabled"
    :readonly="$readonly"
    wire:model.defer="fields.{{ $key }}" />
@endif
