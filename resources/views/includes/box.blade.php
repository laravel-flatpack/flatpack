@if ($key === 'box')
<div x-data="{ closed: {{ Arr::get($options, 'collapsed', false) ? 'true' : 'false' }} }" class="box flex-col">
    <div class="flex flex-row justify-between items-center cursor-pointer" @click="closed = !closed">
        <div class="w-full h-12 flex items-center px-2">{{ Arr::get($options, 'label', '') }}</div>
        <div>
            <span x-show="closed === true"><x-flatpack::icon icon="expand_more" /></span>
            <span x-show="closed === false"><x-flatpack::icon icon="expand_less" /></span>
        </div>
    </div>
    <div x-show="closed === false" class="w-full {{ $fieldset }}">
        @foreach (Arr::get($options, 'fields', []) as $key => $fieldOptions)
            <x-flatpack-form-field
                :key="$key"
                :options="$fieldOptions"
                :entry="$entry"
            />
        @endforeach
    </div>
</div>
@endif
