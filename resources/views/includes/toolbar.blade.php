<div class="form-toolbar">
    <div class="flex flex-wrap items-start justify-end gap-4">

        <div wire:loading.delay.long>
            <x-flatpack::spinner />
        </div>

        @foreach ($elements ?? [] as $key => $options)

            <x-flatpack-action-button
                key="action-{{ $key }}"
                :options="$options"
                :entity="$entity"
                :model="$model"
                />

        @endforeach

    </div>
</div>
