<div class="toolbar">
    <div class="flex flex-wrap items-start justify-end gap-4">
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