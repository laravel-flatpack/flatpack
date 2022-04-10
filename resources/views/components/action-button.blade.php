@if ($action)
    <x-button
        wire:key="button-{{ $key }}"
        wire:loading.attr.delay.long="disabled"
        wire:offline.attr="disabled"
        wire:click.stop="{{ $method }}('{{ $action }}', {{ json_encode($options) }})"
        loading-delay="short"
        :label="$label"
        :primary="$style === 'primary'"
        :secondary="$style === 'secondary' || $style === 'default'"
        :positive="$style === 'success'"
        :negative="$style === 'danger'"
        :warning="$style === 'warning'"
        md
    />
@else
    <x-button
        :href="$link"
        :label="$label"
        :primary="$style === 'primary'"
        :secondary="$style === 'secondary' || $style === 'default'"
        :positive="$style === 'success'"
        :negative="$style === 'danger'"
        :warning="$style === 'warning'"
        md
    />
@endif


@push('scripts')
    @if ($shortcut)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                window.addEventListener("keydown", (event) => {
                    if ((event.ctrlKey || event.metaKey) && event.key == @js($shortcut)) {
                        event.preventDefault();
                        @this.call(@js($method), @js($action), @js($options));
                    }
                });
            });
        </script>
    @endif
@endpush

