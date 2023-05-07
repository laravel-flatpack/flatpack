@if ($type === 'relation')
    <x-select
        x-on:change.debounce="Flatpack.form.inputChange($event, '{{ $binding }}.{{ $key }}')"
        wire:key="{{ $binding }}-{{ $key }}"
        wire:model.defer="{{ $binding }}.{{ $key }}"
        :placeholder="$placeholder"
        :label="$label"
        :async-data="route('flatpack.api.suggestions', [
            'entity' => Flatpack::entityName($relationshipModel),
            'display' => $getFieldOption('relation.display', 'name')
        ])"
        option-label="display"
        option-value="value"
    />
@endif
