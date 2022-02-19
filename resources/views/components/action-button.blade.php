<button
    wire:loading.attr="disabled"
    wire:click="action('{{ $action }}', @js($options))"
    {{ $attributes->class([
        'button text-lg font-medium lg:text-base lg:font-normal',
        'hidden' => $hidden,
        'primary' => $primary,
        'secondary' => !$primary,
    ]) }}
>
    <span class="whitespace-nowrap">{{ $label }}</span>
</button>
