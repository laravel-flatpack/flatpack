<div class="w-full flex flex-col gap-2 {{ $fieldset }}">
@foreach ($elements as $key => $fieldOptions)
    <x-flatpack-form-field
        :key="$key"
        :options="$fieldOptions"
        :entry="$entry"
        :fieldErrors="Arr::get($formErrors, $key, [])"
    />
@endforeach
</div>
