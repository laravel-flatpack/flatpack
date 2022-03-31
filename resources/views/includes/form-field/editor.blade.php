@if ($type === 'editor')
    <div wire:ignore class="flex items-start justify-center w-full mt-1 border-0 border-gray-300 rounded-md">
        <div class="w-full max-w-full prose">
            <textarea
                class="block w-full mx-auto transition duration-150 ease-in-out border-gray-300 resize-none input-editor"
                wire:model.defer="fields.{{ $key }}"
                wire:key="fields-{{ $key }}"
                x-data="Flatpack.ckEditor.editorInstance('fields.{{ $key }}', 'ckeditor-field-{{ $key }}', {
                    upload: '{{ route('flatpack.upload', [ 'entity' => $entity, 'id' => $entry->id ?? 'create' ]) }}',
                    token: '{{ csrf_token() }}',
                })"
                x-init="initEditor()"
                id="ckeditor-field-{{ $key }}">
            </textarea>
        </div>
    </div>

    @section('scripts')
    @parent
    <script src="{{ asset('flatpack/js/ckeditor.js') }}"></script>
    @endsection
@endif
