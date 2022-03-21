<div class="flex w-full px-20 py-16 mx-auto mt-1 border rounded-md shadow-sm">
    <div
        x-data="Flatpack.blockEditor.editorInstance('data', @js($editorId), @js($readOnly), @js($placeholder), @js($logLevel))"
        x-init="initEditor()"
        class="{{ $class }}"
        style="{{ $style }}"
        wire:ignore
    >
        <div id="{{ $editorId }}" class="mx-auto prose"></div>
    </div>
</div>
@section('scripts')
@parent
<script src="{{ asset('flatpack/js/editorjs.js') }}"></script>
@endsection
