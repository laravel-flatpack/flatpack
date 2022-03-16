<div class="form-header">
    <div class="flex flex-col gap-2 {{ $fieldset }}">
    @foreach ($elements as $key => $options)
        <x-flatpack-form-field
            :key="$key"
            :options="$options"
            :entry="$entry"
            :model="$model"
            :entry="$entry"
            :fieldErrors="getErrors($formErrors, $key)"
        />
    @endforeach
    </div>
</div>
