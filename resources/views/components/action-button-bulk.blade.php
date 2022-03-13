<button
    class="flex items-center w-full px-4 py-2 space-x-2 text-sm leading-5 text-gray-700 rounded-sm hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900 dark:text-white dark:hover:bg-gray-600"
    type="button"
    role="menuitem"
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
>
    <span class="whitespace-nowrap">{{ $label }}</span>
</button>
