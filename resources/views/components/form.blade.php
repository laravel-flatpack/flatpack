<div class="flex flex-col gap-4 w-full min-h-screen">
    <div class="flex flex-row justify-between items-start gap-5 w-full">
        <x-flatpack::form.header :elements="$header" :fields="$fields" />
        <x-flatpack::form.toolbar :elements="$toolbar" />
    </div>
    <div class="flex flex-col lg:flex-row w-full gap-5">
        <div class="flex flex-col flex-grow">
            @include('flatpack::includes.form-fields', [
                'fieldset' => 'form-fieldset',
                'fields' => $form
            ])
        </div>
        @if (isset($sidebar) && count($sidebar) > 0)
        <div class="w-full lg:w-1/3 xl:w-1/4">
            @include('flatpack::includes.form-fields', [
                'fieldset' => 'sidebar-fieldset',
                'fields' => $sidebar
            ])
        </div>
        @endif
    </div>
</div>
