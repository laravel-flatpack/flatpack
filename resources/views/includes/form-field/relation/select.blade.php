@if ($type === 'relation')
    <x-select
        x-on:change.debounce="Flatpack.form.inputChange($event, '{{ $binding }}.{{ $key }}')"
        wire:model.defer="{{ $binding }}.{{ $key }}"
        :label="$label"
        :placeholder="$placeholder"
    >
    @foreach ($items as $value => $item)
        <x-select.option
            :label="$item"
            :value="$value"
        />
    @endforeach
    </x-select>
@endif
