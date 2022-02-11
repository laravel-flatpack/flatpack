@if (strtolower($type ?? '') === 'taginput')
<div wire:ignore class="form-field-input">
    <input wire:model="fields.{{ $key }}" id="fields-{{ $key }}" name="fields.{{ $key }}" />
</div>
@php
    // Covert items to array of tags {value:1, name: 'foo'}
    $tags = [];
    foreach ($items as $optionValue => $display) {
        $tags[] = [
            'value' => $optionValue,
            'name' => $display,
            'exists' => true
        ];
    }
    $items = json_encode($tags);
@endphp
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const el = document.getElementById('fields-{{ $key }}');
        const data = JSON.parse(@js($items));

        // Initialize the tags plugin
        Flatpack.taginput('{{ $key }}', el, data, @js($canCreate));
    });
</script>
@endif
