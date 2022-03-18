<div class="form-toolbar">
    <div class="flex flex-wrap items-start justify-end gap-4">
        <div wire:loading.delay.long>
            <x-flatpack::spinner />
        </div>
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
    <div wire:offline class="w-full">
        <div class="flex items-center justify-end w-full h-10 gap-1 text-sm text-red-500">
            <x-flatpack::icon icon="error_outline" />
            <span>{{ __("You are offline."); }}</span>
        </div>
    </div>
</div>
