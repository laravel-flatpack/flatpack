@if (strtolower($type ?? '') === 'toggle')
<x-toggle
    x-on:change.debounce="Flatpack.form.inputChange($event, '{{ $binding }}.{{ $key }}')"
    wire:model.defer="{{ $binding }}.{{ $key }}"
    wire:key="{{ $binding }}-{{ $key }}"
    :label="$label"
    :disabled="$disabled"
    :readonly="$readonly"
    positive
    md
/>
@endif
