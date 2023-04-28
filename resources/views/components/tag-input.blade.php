<div wire:ignore>
    <input
        wire:model="fields.{{ $key }}"
        placeholder="{{ $placeholder }}"
        name="fields.{{ $key }}"
        id="fields-{{ $key }}"
    />
</div>
@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", () => {
        Flatpack.taginput({
            key: '{{ $key }}',
            input: document.getElementById('fields-{{ $key }}'),
            source: '{{ $source }}',
            values: JSON.parse(@js(json_encode($tagInputItems))),
            canCreate: {{ $canCreate ? 'true' : 'false' }},
        });
    });
</script>
@endpush
