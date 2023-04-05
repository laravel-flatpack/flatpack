@if ($action)
    <x-button
        wire:key="button-{{ $key }}"
        x-on:click.stop="Flatpack.action($wire.{{ $method }}, '{{ $action }}', {{ json_encode($options) }})"
        :label="$label"
        :icon="$icon"
        :primary="$style === 'primary'"
        :secondary="!in_array($style, ['primary','success','danger','warning'])"
        :positive="$style === 'success'"
        :negative="$style === 'danger'"
        :warning="$style === 'warning'"
        default
    />
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
@else
    <x-button
        :href="$link"
        :label="$label"
        :icon="$icon"
        :primary="$style === 'primary'"
        :secondary="!in_array($style, ['primary','success','danger','warning'])"
        :positive="$style === 'success'"
        :negative="$style === 'danger'"
        :warning="$style === 'warning'"
        default
    />
    @push('scripts')
        @if ($shortcut)
            <script>
                Flatpack.shortcut(@js($shortcut), {
                    callback: () => window.location.href = @js($link), 
                    options: @js($options)
                });
            </script>
        @endif
    @endpush
@endif

