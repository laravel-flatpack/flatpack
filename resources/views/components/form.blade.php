<div class="flex flex-col gap-10 w-full min-h-screen">
    <div class="flex flex-row justify-between items-start gap-5 w-full my-2">
        @include('flatpack::includes.form-header', [
            'fieldset' => 'header-fieldset',
            'elements' => $header,
            'fields' => $fields,
            'formErrors' => $formErrors,
        ])
        <x-flatpack::form.toolbar :elements="$toolbar" />
    </div>
    <div class="flex flex-col lg:flex-row w-full gap-6">
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
