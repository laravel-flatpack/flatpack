@if (strtolower($type ?? '') === 'textarea')
<x-textarea
    id="fields-{{ $key }}"
    x-on:change.debounce="Flatpack.form.inputChange($event, '{{ $key }}')"
    wire:model.defer="fields.{{ $key }}"
    wire:key="fields-{{ $key }}"
    :label="$label"
    :placeholder="$placeholder"
    :disabled="$disabled"
    :readonly="$readonly"
/>
@endif
