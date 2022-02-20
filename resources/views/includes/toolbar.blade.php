<div>
    <div class="flex flex-row gap-4 lg:flex">
        @foreach ($elements ?? [] as $key => $options)
            @if (isset($options['action']))
                <x-flatpack-action-button key="action-{{ $key }}" :options="$options" :entity="$entity" :model="$model" />
            @endif
        @endforeach
    </div>
</div>
