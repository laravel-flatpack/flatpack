@if ($key === 'box')
<div x-data="{ closed: {{ Arr::get($options, 'collapsed', false) ? 'true' : 'false' }} }" class="flex-col box">
    <div class="flex flex-row items-center justify-between cursor-pointer" @click="closed = !closed">
        <div class="flex items-center w-full h-12 px-2">{{ Arr::get($options, 'label', '') }}</div>
        <div>
            <span x-show="closed === true"><x-icon name="chevron-up" style="solid" class="w-4 h-4" /></span>
            <span x-show="closed === false"><x-icon name="chevron-down" style="solid" class="w-4 h-4" /></span>
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
