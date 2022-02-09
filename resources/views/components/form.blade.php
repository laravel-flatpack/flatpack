<div x-cloak x-data class="flex flex-col w-full min-h-screen gap-5">
    <div class="flex flex-col-reverse items-start justify-start w-full gap-5 my-2 lg:justify-between lg:flex-row">
        @include('flatpack::includes.form-header', [
            'fieldset' => 'header-fieldset',
            'elements' => $header,
            'fields' => $fields,
            'formErrors' => $formErrors,
        ])
        <x-flatpack::form.toolbar :elements="$toolbar" />
    </div>
    <div class="flex flex-col w-full gap-6 lg:flex-row">
        <div class="form-main">
            @include('flatpack::includes.form-fields', [
                'fieldset' => 'form-fieldset',
                'fields' => $main,
                'formErrors' => $formErrors,
            ])
        </div>
        @if (isset($sidebar) && count($sidebar) > 0)
        <div class="form-sidebar">
            @include('flatpack::includes.form-fields', [
                'fieldset' => 'sidebar-fieldset',
                'fields' => $sidebar,
                'formErrors' => $formErrors,
            ])
        </div>
        @endif
    </div>
</div>
