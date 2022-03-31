@if ($type === 'block-editor')
    <livewire:flatpack.block-editor
        :editor-id="$key"
        :value="$entry->{$key}"
        :read-only="$readonly"
        class="w-full"
    />
@endif
