<button
    {{ $attributes->merge([
        'class' => 'button'
    ])}}
>
    <span>{{$slot}}</span>
</button>
