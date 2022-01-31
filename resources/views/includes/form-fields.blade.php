@php
    $group = null;
    $groupFirst = true;
@endphp
@foreach ($fields as $key => $options)
    @if ($key === 'tabs')
        @include('flatpack::includes.tabs')
    @else
        @if ($loop->first)
            <div x-data="{ open: true }" class="box">
        @else
            @if ($group !== Arr::get($options, 'group'))
                    </div>
                </div>
                <div x-data="{ open: true }" class="box">
                @php $groupFirst = true @endphp
            @else
                @php $groupFirst = false @endphp
            @endif
        @endif

        @if ($loop->first || $groupFirst)
            @if (Arr::has($options, 'group'))
            <div class="flex justify-between items-center cursor-pointer" @click="open = !open">
                <div>{{ $options['group'] }}</div>
                <span x-show="!open"><x-flatpack::icon icon="expand_more" /></span>
                <span x-show="open"><x-flatpack::icon icon="expand_less" /></span>
            </div>
            @endif
            <div x-show="open" class="{{ $fieldset }}">
        @endif

        <x-flatpack-form-field
            :key="$key"
            :options="$options"
            :entry="$entry"
        />

        @php
            $group = Arr::get($options, 'group');
        @endphp
        @if ($loop->last)
                </div>
            </div>
        @endif

    @endif
@endforeach
