<div class="w-full flex flex-col gap-5">
@foreach ($elements as $key => $fieldOptions)
    <x-flatpack-form-field
        :key="$key"
        :options="$fieldOptions"
        :entry="$entry"
        :fieldErrors="Arr::get($formErrors, $key, [])"
    />
@endforeach
</div>
