@if ($type === 'relation')
    <x-select
        x-on:change.debounce="Flatpack.form.inputChange($event, '{{ $binding }}.{{ $key }}')"
        wire:model.defer="{{ $binding }}.{{ $key }}"
        :placeholder="$placeholder"
        :label="$label"
        :async-data="route('flatpack.api.suggestions', $entity)"
        option-label="display"
        option-value="value"
    />
@endif
