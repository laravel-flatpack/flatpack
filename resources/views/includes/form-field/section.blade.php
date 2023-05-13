@if ($type === 'section')
<div class="col-span-2">
    <h2 class="text-base font-semibold leading-7 text-gray-800">{{ data_get($options, 'title') }}</h2>
    <p class="text-sm leading-6 text-gray-500">{{ data_get($options, 'description') }}</p>
</div>
@endif
