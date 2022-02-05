@if (strtolower($type ?? '') === 'editor')
<div wire:ignore class="flex justify-center items-start w-full border rounded-md shadow-sm mt-1">
    <div class="prose w-full max-w-full">
        <textarea
            class="input-editor block w-full mx-auto resize-none border-gray-300 rounded-md shadow-sm transition duration-150 ease-in-out"
            wire:model.stop="fields.{{ $key }}"
            wire:key="fields-{{ $key }}"
            x-data x-init="
            ClassicEditor
            .create($refs.fields_{{ $key }})
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
