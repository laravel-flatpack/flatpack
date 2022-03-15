<div class="flex w-full px-20 py-16 mx-auto mt-1 border rounded-md shadow-sm">
    <div
        x-data="editorInstance('data', '{{ $editorId }}', {{ $readOnly ? 'true' : 'false' }}, '{{ $placeholder }}', '{{ $logLevel }}')"
        x-init="initEditor()"
        class="{{ $class }}"
        style="{{ $style }}"
        wire:ignore
    >
        <div id="{{ $editorId }}" class="mx-auto prose"></div>
    </div>
</div>
