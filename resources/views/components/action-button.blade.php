<div x-data="{ modalOpen: false }">
@if ($confirm)
    @include('flatpack::includes.modal-confirm')
@endif
    <button
        @if ($confirm)
        @click="modalOpen = !modalOpen"
        @else
        wire:loading.attr="disabled"
        wire:click="{{ $method }}('{{ $action }}', @js($options))"
        @endif
        wire:key="{{ $key }}"
        {{ $attributes->class([
            'button text-lg font-medium lg:text-base lg:font-normal',
            $style,
            'hidden' => $hidden,
        ]) }}
    >
        <span class="whitespace-nowrap">{{ $label }}</span>
    </button>
</div>
