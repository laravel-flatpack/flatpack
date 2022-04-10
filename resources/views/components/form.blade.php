<div x-cloak x-data id="form" class="block w-full min-h-screen {{ $hasChanges ? 'has-unsaved-changes' : 'is-saved' }}">

    <div class="grid grid-flow-row-dense grid-cols-12 gap-5 py-10">

        <div class="col-span-12 md:col-span-7 lg:col-span-8 xl:col-span-9">
            @include('flatpack::includes.form-header', [
                'fieldset' => 'header-fieldset',
                'elements' => $header,
                'fields' => $fields,
            ])
        </div>

        <div class="col-span-12 md:col-span-5 lg:col-span-4 xl:col-span-3">
            @include('flatpack::includes.toolbar', [
                'elements' => $toolbar,
                'entity' => $entity,
                'model' => $model,
            ])
        </div>

    </div>

    @if (count($sidebar) > 0)
    <div class="grid grid-flow-row-dense grid-cols-12 gap-5">

        <div class="col-span-12 lg:col-span-8 xl:col-span-9">
            <div class="form-main">
                @include('flatpack::includes.form-fields', [
                    'fieldset' => 'form-fieldset',
                    'fields' => $main,
                    'entity' => $entity,
                    'model' => $model,
                ])
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4 xl:col-span-3">
            <div class="form-sidebar">
                @include('flatpack::includes.form-fields', [
                    'fieldset' => 'sidebar-fieldset',
                    'fields' => $sidebar,
                    'entity' => $entity,
                    'model' => $model,
                ])
            </div>
        </div>

    </div>

    @else

    <div class="w-full max-w-3xl mx-auto">
        <div class="form-main">
            @include('flatpack::includes.form-fields', [
                'fieldset' => 'form-fieldset',
                'fields' => $main,
                'entity' => $entity,
                'model' => $model,
            ])
        </div>
    </div>

    @endif

</div>
