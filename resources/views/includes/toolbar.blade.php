<div class="form-sidebar">
    <div class="flex flex-wrap items-start justify-end gap-4">
    @foreach ($elements ?? [] as $key => $options)
        @if (isset($options['action']))
            <x-flatpack-action-button
                key="action-{{ $key }}"
                :style="($loop->first && count($elements) === 1) ? 'primary' : ''"
                :options="$options"
                :entity="$entity"
                :model="$model"
                />
        @endif
    @endforeach
    </div>
</div>
