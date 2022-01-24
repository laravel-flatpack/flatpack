@if (strtolower($type ?? '' ?? '') === 'blockeditor')
@php
$editor = $value;
@endphp
<div class="w-full flex rounded-md shadow-sm mt-1 border px-20 py-16">
    <livewire:flatpack.blockeditor
        editor-id="flatpack-editor"
        :value="$editor"
        upload-disk="public"
        download-disk="public"
        class="w-full"
        :read-only="false"
        placeholder="Start writing..."
        />
</div>
@endif
