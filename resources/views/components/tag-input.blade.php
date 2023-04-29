<div wire:ignore>
    <input
        wire:key="{{ $binding }}-{{ $key }}"
        wire:model="{{ $binding }}.{{ $key }}"
        placeholder="{{ $placeholder }}"
        name="{{ $binding }}.{{ $key }}"
        id="{{ $binding }}-{{ $key }}"
    />
</div>
@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", () => {
        Flatpack.taginput({
            key: '{{ $key }}',
            input: document.getElementById('{{ $binding }}-{{ $key }}'),
            source: '{{ $source }}',
            values: JSON.parse(@js(json_encode($tagInputItems))),
            canCreate: {{ $canCreate ? 'true' : 'false' }},
        });
    });
</script>
@endpush
