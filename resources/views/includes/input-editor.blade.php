@if (strtolower($type ?? '') === 'editor')

<script src="https://cdn.ckeditor.com/ckeditor5/30.0.0/classic/ckeditor.js"></script>
<style>
:root {
  --ck-border-radius: 0.375rem;
  --ck-custom-border: rgb(209 213 219);
  --ck-custom-focus-border: rgb(199 210 254);
  --ck-color-panel-border: var(--ck-custom-border);
  --ck-color-toolbar-border: var(--ck-custom-border);
  --ck-color-base-border: var(--ck-custom-border);
  --ck-color-focus-border: var(--ck-custom-focus-border);
}
.ck.ck-reset.ck-editor {
  min-height: 300px;
  width: 100%;
}
.ck.ck-content {
  min-height: 320px;
}
</style>
<div wire:ignore class="flex justify-center items-start w-full border rounded-md shadow-sm mt-1">
    <div class="prose w-full max-w-full">
        <textarea
            class="block w-full mx-auto resize-none border-gray-300 rounded-md shadow-sm transition duration-150 ease-in-out"
            wire:model.stop="fields.{{ $key }}"
            wire:key="fields-{{ $key }}"
            x-data x-init="
            ClassicEditor
            .create($refs.fields{{ $key }})
            .then(function(editor){
                editor.model.document.on('change:data', () => {
                    @this.set('fields.{{ $key }}', editor.getData())
                })
            })
            .catch( error => {
                console.error( error );
            } );" x-ref="fields{{ $key }}">
        </textarea>
    </div>
</div>
@endif
