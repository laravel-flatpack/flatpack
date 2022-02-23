@if ($key === 'tabs')
<div x-data="{ openTab: 0, active: 'font-bold', inactive: 'font-normal' }" class="w-full col-span-full">
    <ul class="flex w-full mx-auto border-b border-gray-300">
        @foreach ($options as $tab => $tabOptions)
        <li @click="openTab={{ $loop->index }}">
            <a class="block p-2 px-3 cursor-pointer" :class="openTab === {{ $loop->index }} ? active : inactive">
                <span>{{ Arr::get($tabOptions, 'label', $tab) }}</span>
            </a>
        </li>
        @endforeach
    </ul>
    <div class="w-full pt-4">
    @foreach ($options as $tab => $tabOptions)
        <div x-show="openTab === {{ $loop->index }}">
            @include('flatpack::includes.form-fields', [
                'fieldset' => 'form-fieldset',
                'fields' => Arr::get($tabOptions, 'fields', []),
                'formErrors' => $formErrors,
            ])
        </div>
    @endforeach
    </div>
</div>
@endif
