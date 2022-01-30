@if (strtolower($type ?? '') === 'taginput')
<div wire:ignore class="w-full flex rounded-md shadow-sm mt-1">
    <input wire:model="fields.{{ $key }}" id="fields-{{ $key }}" name="fields.{{ $key }}" />
</div>
@php
    // Covert items to array of tags {value:1, name: 'foo'}
    $tags = [];
    foreach ($items as $optionValue => $display) {
        $tags[] = ['value' => $optionValue, 'name' => $display ];
    }
    $items = json_encode($tags);
@endphp
<script>
    document.addEventListener("DOMContentLoaded", () => {
        Flatpack.taginput(
            document.getElementById('fields-{{ $key }}'),
            JSON.parse(@js($items))
        );
    });
</script>
@endif
