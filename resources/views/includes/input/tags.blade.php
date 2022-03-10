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

        // Initialize the tags plugin
        Flatpack.taginput('{{ $key }}', el, JSON.parse(@js($items)), @js($canCreate));
    });
</script>
@endif
