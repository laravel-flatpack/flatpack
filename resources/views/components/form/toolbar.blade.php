@props(['elements' => [], 'fields' => []])

<div>
    <div class="flex flex-row gap-4 lg:flex">
        @foreach ($elements ?? [] as $key => $options)
            @if (isset($options['action']))
                <x-flatpack-action-button :key="$key" :options="$options" />
            @endif
        @endforeach
    </div>
</div>
