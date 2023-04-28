@if ($type === 'select')
    <x-select
        x-on:change.debounce="Flatpack.form.inputChange($event, '{{ $key }}')"
        wire:model.defer="fields.{{ $key }}"
        wire:key="fields-{{ $key }}"
        :label="$label"
        :placeholder="$placeholder"
        :clearable="false"
        :searchable="false"
    >
    @foreach ($items as $value => $item)
        <x-select.option :label="$item" :value="$value" />
    @endforeach
</x-select>
@endif
