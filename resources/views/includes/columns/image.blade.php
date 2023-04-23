<img
    src="{{ $path }}" 
    class="rounded h-10 w-12 object-cover mx-0 -my-2"
    
    {!! count($attributes) ? $column->arrayToAttributes($attributes) : '' !!}
/>
