@props(['elements' => [], 'fields' => []])

<div class="flex flex-row">
    @foreach ($elements ?? [] as $key => $element)
    @if (isset($element['action']))
        <button
            wire:click="action('{{ Arr::get($element, 'action') }}', {{ Arr::get($element, 'close', '0') }})"
            class="button {{ Arr::get($element, 'hidden', false) ? 'hidden' : '' }}">
            <span class="whitespace-nowrap">{{ Arr::get($element, 'label') }}</span>
        </button>
    @endif
    @endforeach
<script>
// Action buttons keyboard shortcuts
const shortcuts = {};
@foreach ($elements ?? [] as $key => $element)
@if (isset($element['shortcut']) && isset($element['action']))
shortcuts.{{ Str::lower($element['shortcut']) }} = {
    action: '{{ $element['action'] }}',
    close: '{{ Arr::get($element, 'close', '0') }}'
};
@endif
@endforeach
const handleKeyboard = e => {
  const { repeat, metaKey, ctrlKey, key } = e;
  const shortcut = key.toLowerCase();
  if (repeat) return;
  if ((metaKey || ctrlKey) && Object.keys(shortcuts).includes(shortcut)) {
    e.preventDefault()
    const { action, close } = shortcuts[shortcut];
    return @this.action(action, close || false);
  }
  return;
}
document.addEventListener('keydown', handleKeyboard);
</script>
</div>
