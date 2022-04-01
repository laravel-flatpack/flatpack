@if ($type === 'select')
    <x-select
        :label="$label"
        :placeholder="$placeholder"
        wire:model.defer="fields.{{ $key }}"
    >
    @foreach ($items as $value => $item)

        <x-select.option :label="$item" :value="$value" />

    @endforeach
</x-select>
@endif
