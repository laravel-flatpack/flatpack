@if ($type === 'button')
    <x-flatpack-action-button
        key="action-{{ $key }}"
        :options="$options"
        :entity="$entity"
        :model="$model"
        :entry="$entry"
        class="self-center w-fit h-fit"
    />
@endif
