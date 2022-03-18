<div x-cloak x-data id="form" class="flex flex-col w-full min-h-screen gap-3 {{ $hasChanges ? 'has-unsaved-changes' : 'is-saved' }}">
    <div class="flex flex-col-reverse items-start justify-start w-full gap-5 my-2 lg:justify-between lg:flex-row">
        @include('flatpack::includes.form-header', [
            'fieldset' => 'header-fieldset',
            'elements' => $header,
            'fields' => $fields,
            'formErrors' => $formErrors,
        ])
        @include('flatpack::includes.toolbar', [
            'elements' => $toolbar,
            'entity' => $entity,
            'model' => $model,
            'formErrors' => $formErrors,
        ])
    </div>
    <div class="flex flex-col w-full gap-6 lg:flex-row">
        <div class="form-main">
            @include('flatpack::includes.form-fields', [
                'fieldset' => 'form-fieldset',
                'fields' => $main,
                'entity' => $entity,
                'model' => $model,
                'formErrors' => $formErrors,
            ])
        </div>
        @if (isset($sidebar) && count($sidebar) > 0)
        <div class="form-sidebar">
            @include('flatpack::includes.form-fields', [
                'fieldset' => 'sidebar-fieldset',
                'fields' => $sidebar,
                'entity' => $entity,
                'model' => $model,
                'formErrors' => $formErrors,
            ])
        </div>
        @endif
    </div>
</div>
