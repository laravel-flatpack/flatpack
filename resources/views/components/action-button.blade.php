<button
    wire:key="{{ $key }}"
    wire:loading.attr.delay.long="disabled"
    wire:offline.attr="disabled"
    {{ $attributes->class([
        'button text-md font-medium lg:text-sm',
        $style,
        'hidden' => $hidden,
    ]) }}
    @if ($confirm)
    @click="event.stopImmediatePropagation(); Flatpack.confirm(@js($confirmationMessage), @js([
            'title' => $label,
            'action' => $label,
            'style' => $style,
        ])).then(({ isConfirmed }) => isConfirmed && @this.{{ $method }}('{{ $action }}', @js($options)));"
    @else
    wire:click="{{ $method }}('{{ $action }}', @js($options))"
    @endif
    @if(isset($options['shortcut']))
        @keydown.window.prevent.cmd.{{ $options['shortcut'] }}="@this.{{ $method }}('{{ $action }}', @js($options))"
        @keydown.window.prevent.ctrl.{{ $options['shortcut'] }}="@this.{{ $method }}('{{ $action }}', @js($options))"
    @endif
>
    <span class="whitespace-nowrap">{{ $label }}</span>
</button>
