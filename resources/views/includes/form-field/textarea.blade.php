@if (strtolower($type ?? '') === 'textarea')
<x-textarea
    x-on:change.debounce="Flatpack.form.inputChange($event, '{{ $binding }}.{{ $key }}')"
    wire:model.defer="{{ $binding }}.{{ $key }}"
    wire:key="{{ $binding }}-{{ $key }}"
    id="{{ $binding }}-{{ $key }}"
    :label="$label"
    :placeholder="$placeholder"
    :disabled="$disabled"
    :readonly="$readonly"
/>
@endif
