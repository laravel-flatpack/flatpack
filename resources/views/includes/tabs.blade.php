@if ($key === 'tabs')
<div x-data="{ openTab: 0, active: 'font-bold', inactive: 'font-normal' }" class="col-span-full w-full">
    <ul class="flex border-b border-gray-300 w-full mx-auto">
        @foreach ($options as $tab => $tabOptions)
        <li @click="openTab={{ $loop->index }}">
            <a class="block cursor-pointer p-2 px-3" :class="openTab === {{ $loop->index }} ? active : inactive">
                <span>{{ Arr::get($tabOptions, 'label', $tab) }}</span>
            </a>
        </li>
        @endforeach
    </ul>
    <div class="w-full pt-4">
    @foreach ($options as $tab => $tabOptions)
        <div x-show="openTab === {{ $loop->index }}" class="box form-fieldset">
            @foreach (Arr::get($tabOptions, 'fields', []) as $key => $fieldOptions)
                <x-flatpack-form-field
                    :key="$key"
                    :options="$fieldOptions"
                    :entry="$entry"
                />
            @endforeach
        </div>
    @endforeach
    </div>
</div>
@endif
