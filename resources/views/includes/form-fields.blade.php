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
                        <span x-cloak x-show="!open"><x-flatpack::icon icon="expand_more" size="small" /></span>
                        <span x-cloak x-show="open"><x-flatpack::icon icon="expand_less" size="small" /></span>
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
                    :fieldErrors="getErrors($formErrors, $key)"
                />

        @if($loop->last)
                </div>
            </div>
        @endif
    @endif
@endforeach
@endforeach
</div>
