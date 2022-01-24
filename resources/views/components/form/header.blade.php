@props(['elements' => [], 'fields' => []])
<div class="w-full">
@foreach ($elements as $key => $options)
    <x-flatpack::form.heading-field
        :key="$key"
        :type="Arr::get($options, 'type')"
        :label="Arr::get($options, 'label')"
        :placeholder="Arr::get($options, 'placeholder')"
        :value="Arr::get($fields, $key)"
        :size="Arr::get($options, 'size')"
        :disabled="Arr::get($options, 'disabled', false)"
        :readonly="Arr::get($options, 'readonly', false)"
        :required="Arr::get($options, 'required', false)"
    />
@endforeach
</div>
