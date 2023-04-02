@if ($action)
    <x-button
        wire:key="button-{{ $key }}"
        wire:offline.attr="disabled"
        x-on:click.stop="Flatpack.action($wire.{{ $method }}, '{{ $action }}', {{ json_encode($options) }})"
        :label="$label"
        :icon="$icon"
        :primary="$style === 'primary'"
        :secondary="$style === 'secondary' || $style === 'default'"
        :positive="$style === 'success'"
        :negative="$style === 'danger'"
        :warning="$style === 'warning'"
        default
    />
@else
    <x-button
        :href="$link"
        :label="$label"
        :icon="$icon"
        :primary="$style === 'primary'"
        :secondary="$style === 'secondary' || $style === 'default'"
        :positive="$style === 'success'"
        :negative="$style === 'danger'"
        :warning="$style === 'warning'"
        default
    />
@endif

@push('scripts')
    @if ($shortcut)
        <script>
            Flatpack.shortcut(@js($shortcut), {
                callback: () => @this.call(@js($method), @js($action), @js($options)), 
                action: @js($action), 
                options: @js($options)
            });
        </script>
    @endif
@endpush

