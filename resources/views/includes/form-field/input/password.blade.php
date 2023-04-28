@if (strtolower($type) === 'password')
<x-input
    type="password"
    x-on:change.debounce="Flatpack.form.inputChange($event, '{{ $key }}')"
    wire:model.defer="fields.{{ $key }}"
    wire:key="fields-{{ $key }}"
    autocomplete="new-password"
    :label="$label"
    :placeholder="$placeholder"
    :disabled="$disabled"
    :readonly="$readonly"
/>
@endif
