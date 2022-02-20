<button
    wire:key="{{ $key }}"
    {{ $attributes->class([
        'button text-lg font-medium lg:text-base lg:font-normal',
        $style,
        'hidden' => $hidden,
    ]) }}
    @if ($confirm)
    @click="event.stopImmediatePropagation(); Flatpack.confirm(@js($confirmationMessage), {
        title: @js($label),
        action: @js($label),
        style: @js($style)
    }).then(({ isConfirmed }) => isConfirmed && @this.{{ $method }}('{{ $action }}', @js($options)));"
    @else
    wire:loading.attr="disabled"
    wire:click="{{ $method }}('{{ $action }}', @js($options))"
    @endif
    @if(isset($options['shortcut']))
        @keydown.window.prevent.cmd.{{ $options['shortcut'] }}="@this.{{ $method }}('{{ $action }}', @js($options))"
        @keydown.window.prevent.ctrl.{{ $options['shortcut'] }}="@this.{{ $method }}('{{ $action }}', @js($options))"
    @endif
>
    <span class="whitespace-nowrap">{{ $label }}</span>
</button>
