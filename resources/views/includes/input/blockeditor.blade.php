@if (strtolower($type ?? '') === 'blockeditor')
<div class="flex w-full px-20 py-16 mt-1 border rounded-md shadow-sm">
    <livewire:flatpack.block-editor
        :editor-id="$key"
        :value="$value"
        :read-only="$readonly"
        :placeholder="$placeholder"
        class="w-full"
    />
</div>
@endif
