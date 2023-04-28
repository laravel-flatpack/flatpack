@if (strtolower($type ?? '') === 'toggle')
<x-toggle
    x-on:change.debounce="Flatpack.form.inputChange($event, '{{ $key }}')"
    wire:model.defer="fields.{{ $key }}"
    wire:key="fields-{{ $key }}"
    :label="$label"
    :disabled="$disabled"
    :readonly="$readonly"
    positive
    md
/>
@endif
