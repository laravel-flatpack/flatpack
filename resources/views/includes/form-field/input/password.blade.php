@if (strtolower($type) === 'password')
<x-input
    type="password"
    x-on:change.debounce="Flatpack.form.inputChange($event, '{{ $binding }}.{{ $key }}')"
    wire:model.defer="{{ $binding }}.{{ $key }}"
    wire:key="{{ $binding }}-{{ $key }}"
    autocomplete="new-password"
    :label="$label"
    :placeholder="$placeholder"
    :disabled="$disabled"
    :readonly="$readonly"
/>
@endif
