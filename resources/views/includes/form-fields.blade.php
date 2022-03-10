<div>
@php
    $group = null;
    $groupFirst = true;
@endphp
@foreach ($fields as $key => $options)
    @if ($key === 'tabs')
        @include('flatpack::includes.tabs', [
            'options' => $options,
            'formErrors' => $formErrors
        ])
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
            <div class="flex items-center justify-between text-gray-500 cursor-pointer" @click="open = !open">
                <div class="text-xs font-bold uppercase">{{ $options['group'] }}</div>
                <span x-cloak x-show="!open"><x-flatpack::icon icon="expand_more" size="small" /></span>
                <span x-show="open"><x-flatpack::icon icon="expand_less" size="small" /></span>
            </div>
            @endif
            <div x-show="open" class="{{ $fieldset }}">
        @endif

        @if (isset($options['type']) && $options['type'] === 'button' && isset($options['action']))
            <div class="col-span-full @if(Arr::get($options, 'span', 'full') !== 'full') lg:col-span-1 @endif">
                <x-flatpack-action-button
                    key="action-{{ $key }}"
                    :options="$options"
                    :entity="$entity"
                    :model="$model"
                    class="self-center w-fit h-fit"
                />
            </div>
        @elseif (isset($options['type']) && $options['type'] === 'image-upload')
            <div class="col-span-full @if (Arr::get($options, 'span', 'full') !== 'full') lg:col-span-1 @endif">
                <livewire:flatpack.image-uploader
                    :name="$key"
                    :options="$options"
                    :entity="$entity"
                    :model="$model"
                    :entry="$entry"
                />
            </div>
        @else
            <x-flatpack-form-field
                :key="$key"
                :options="$options"
                :entry="$entry"
                :fieldErrors="Arr::get($formErrors, $key, [])"
            />
        @endif

        @php
            $group = Arr::get($options, 'group');
        @endphp
        @if ($loop->last)
                </div>
            </div>
        @endif

    @endif
@endforeach
</div>
