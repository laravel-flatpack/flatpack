@if (strtolower($type ?? '') === 'editor')
<div wire:ignore class="flex items-start justify-center w-full mt-1 border-0 border-gray-300 rounded-md">
    <div class="w-full max-w-full prose">
        <textarea
            class="block w-full mx-auto transition duration-150 ease-in-out border-gray-300 resize-none input-editor"
            wire:model.stop="fields.{{ $key }}"
            wire:key="fields-{{ $key }}"
            x-data x-init="
            Flatpack.editor.classic.create($refs.fields_{{ $key }}, Flatpack.editorConfig())
            .then(function(editor){
                editor.model.document.on('change:data', () => {
                    @this.set('fields.{{ $key }}', editor.getData())
                })
            })
            .catch( error => {
                console.error( error );
            } );" x-ref="fields_{{ $key }}">
        </textarea>
    </div>
</div>
@endif
