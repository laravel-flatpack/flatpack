<div x-cloak x-data id="form" class="flex flex-col w-full min-h-screen {{ $hasChanges ? 'has-unsaved-changes' : 'is-saved' }}">

    <div class="flex flex-col-reverse items-start justify-start w-full gap-5 my-10 lg:justify-between lg:flex-row">
        @include('flatpack::includes.form-header', [
            'fieldset' => 'header-fieldset',
            'elements' => $header,
            'fields' => $fields,
        ])
        @include('flatpack::includes.toolbar', [
            'elements' => $toolbar,
            'entity' => $entity,
            'model' => $model,
        ])
    </div>

    <div class="flex flex-col w-full gap-6 lg:flex-row">
        <div class="form-main">
            @include('flatpack::includes.form-fields', [
                'fieldset' => 'form-fieldset',
                'fields' => $main,
                'entity' => $entity,
                'model' => $model,
            ])
        </div>
        @if (isset($sidebar) && count($sidebar) > 0)
        <div class="form-sidebar">
            @include('flatpack::includes.form-fields', [
                'fieldset' => 'sidebar-fieldset',
                'fields' => $sidebar,
                'entity' => $entity,
                'model' => $model,
            ])
        </div>
        @endif
    </div>

</div>
