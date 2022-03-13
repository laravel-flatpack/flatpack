<div>
    <div class="flex flex-row gap-4 lg:flex">
        @foreach ($elements ?? [] as $key => $options)
            @php
                if ($loop->first && count($elements) === 1) {
                    $style = 'primary';
                } else {
                    $style = '';
                }
            @endphp
            @if (isset($options['action']))
                <x-flatpack-action-button
                    key="action-{{ $key }}"
                    :style="$style"
                    :options="$options"
                    :entity="$entity"
                    :model="$model"
                    />
            @endif
        @endforeach
    </div>
</div>
