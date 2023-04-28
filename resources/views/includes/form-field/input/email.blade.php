@if (strtolower($type) === 'email')
<x-input
    type="email"
    x-on:change.debounce="Flatpack.form.inputChange($event, '{{ $key }}')"
    wire:model.defer="fields.{{ $key }}"
    wire:key="fields-{{ $key }}"
    :label="$label"
    :placeholder="$placeholder"
    :disabled="$disabled"
    :readonly="$readonly"
/>
@endif
