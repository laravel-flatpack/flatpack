<div class="single-image">
    @if ($image instanceof \Livewire\TemporaryUploadedFile)
        <img src="{{ $image->temporaryUrl() }}" alt="uploaded-image">
        <label>{{ $image->getClientOriginalName() }}</label>
    @else
        <img src="{{ $image }}" alt="{{ $image }}">
    @endif
    <button
        type="button"
        wire:loading.attr="disabled" wire:target="handleRemoveImage('{{ $index }}')"
        wire:click.prevent="handleRemoveImage('{{ $index }}')"
    >
        <x-flatpack::icon icon="close" size="small" />
    </button>
</div>
