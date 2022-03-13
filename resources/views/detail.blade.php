<x-flatpack-layout>
    <livewire:flatpack.form
        :model="$model"
        :entity="$entity"
        :entry="$entry"
        :composition="$composition"
        :formType="$formType"
        />

@section('scripts')
@parent
<script src="{{ asset('flatpack/js/form-components.js') }}"></script>
<script src="{{ asset('flatpack/js/editorjs.js') }}"></script>
<script>
function UploadAdapterPlugin (editor) {
    if (!window?.Flatpack?.editor?.UploadAdapter) {
        return;
    }
    editor.plugins.get("FileRepository").createUploadAdapter = (loader) => {
        return new window.Flatpack.editor.UploadAdapter(
            loader,
            "{{ route('flatpack.upload', [ 'entity' => request()->entity, 'id' => request()->id ]) }}",
            "{{ csrf_token() }}"
        );
    };
}

window.Flatpack = {
    ...window.Flatpack,
    editorConfig: () => ({ extraPlugins: [UploadAdapterPlugin] }),
}
</script>
@endsection
</x-flatpack-layout>
