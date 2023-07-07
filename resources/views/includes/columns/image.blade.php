@if (empty($path))
<img
    src="{{ flatpackAsset('flatpack/images/placeholder.png') }}"
    class="rounded h-8 w-10 object-cover mx-0 -my-2"
/>
@else
<img
    src="{{ $path }}" 
    class="rounded h-8 w-10 object-cover mx-0 -my-2"
    style="max-width: 40px"
    
    {!! count($attributes) ? $column->arrayToAttributes($attributes) : '' !!}
/>
@endif
