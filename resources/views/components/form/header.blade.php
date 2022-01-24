@props(['elements' => [], 'fields' => []])
<div class="w-full">
@foreach ($elements as $key => $options)
    @php
        $value = Arr::get($fields, Arr::get($options, 'value', $key));
        $label = Arr::get($options, 'label');
    @endphp
    @include('flatpack::includes.heading-title')
    @include('flatpack::includes.heading-subtitle')
@endforeach
</div>
