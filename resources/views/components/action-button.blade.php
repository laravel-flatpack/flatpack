<x-button
    wire:key="button-{{ $key }}"
    wire:loading.attr.delay.long="disabled"
    wire:offline.attr="disabled"
    wire:click.stop="{{ $method }}('{{ $action }}', {{ json_encode($options) }})"
    loading-delay="short"
    :label="$label"
    :dark="$style === 'primary'"
    :secondary="$style === 'secondary' || $style === 'default'"
    :positive="$style === 'success'"
    :negative="$style === 'danger'"
    :warning="$style === 'warning'"
    md
/>
