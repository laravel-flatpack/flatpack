@if ($type === 'block-editor')
    <livewire:flatpack.block-editor
        :editorId="$key"
        :value="$entry->{$key}"
        :readOnly="$readonly"
        class="w-full"
    />
@endif
