<div>
@foreach (groupComposition($fields) as $groups)
@foreach ($groups as $key => $options)
    @if ($key === 'tabs')
        @include('flatpack::includes.tabs', [
            'options' => $options,
            'formErrors' => $formErrors
        ])
    @else
        @if ($loop->first)
            <div x-data="{ open: true }" class="box">

                @if (getOption($options, 'group') !== null)
                    <div class="flex items-center justify-between text-gray-500 cursor-pointer" @click="open = !open">
                        <div class="text-xs font-bold uppercase">{{ getOption($options, 'group') }}</div>
                        <span x-show="closed === true"><x-icon name="chevron-down" style="solid" class="w-4 h-4" /></span>
                        <span x-show="closed === false"><x-icon name="chevron-up" style="solid" class="w-4 h-4" /></span>
                    </div>
                @endif

                <div x-show="open" class="{{ $fieldset }}">
        @endif

            <x-flatpack-form-field
                :key="$key"
                :options="$options"
                :entity="$entity"
                :model="$model"
                :entry="$entry"
            />

        @if($loop->last)
                </div>
            </div>
        @endif
    @endif
@endforeach
@endforeach
</div>
