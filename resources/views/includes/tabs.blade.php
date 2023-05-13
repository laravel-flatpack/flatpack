@if ($key === "tabs")
<div x-data="{ openTab: 0, active: 'is-active', inactive: 'is-not-active' }" class="w-full col-span-full">
    <ul aria-label="{{ __('Tabs') }}" class="tab-list-root">
        @foreach ($options as $tab => $tabOptions)
        <li>
            <button
                x-on:click="openTab={{ $loop->index }}"
                :class="openTab === @js($loop->index) ? active : inactive"
                class="tab-root"
                value="{{ $loop->index }}">
                <span class="tab-text">{{ data_get($tabOptions, 'label', Str::ucfirst($tab)) }}</span>
            </button>
        </li>
        @endforeach
    </ul>
    <div class="w-full pt-4">
        @foreach ($options as $tab => $tabOptions)
        <div x-show="openTab === {{ $loop->index }}">
            @include('flatpack::includes.form-fields', [
                'fieldset' => 'form-fieldset',
                'fields' => data_get($tabOptions, 'fields', []),
                'formErrors' => $formErrors,
            ])
        </div>
        @endforeach
    </div>
</div>
@endif
