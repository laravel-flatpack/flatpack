<div class="image-uploader-container {{ $multiple ? "is-multiple" : "" }} {{ $preview === 'full' ? "is-preview-full" : "is-preview-auto" }}">
    @if (!empty($images))
    <div class="image-wrapper">
        @foreach ($images as $index => $image)
            @include('flatpack::includes.image.preview')
        @endforeach
    </div>
    @endif

    @if ($multiple || count($images) === 0)
    <div class="input-wrapper">
        <input id="imagesInput" type="file" accept="image/*" wire:model="rawImages" {{ $multiple ? 'multiple' : null }}>
        <div class="drop-zone">
            <div wire:loading wire:target="rawImages">
                {{ __("Uploading...") }}
            </div>

            <p wire:loading.remove wire:target="rawImages" class="text-gray-400">
                @if ($multiple)
                    Drop files here to upload
                    <br />
                    <em>or</em>
                    <br />
                    Select Files
                @else
                    Drop file here to upload
                    <br />
                    <em>or</em>
                    <br />
                    Select File
                @endif
            </p>
        </div>
    </div>
    @endif

    @error('rawImages.*')
    <div class="block w-full text-sm text-left text-red-500">
        <span>{{ $message }}</span>
    </div>
    @enderror
</div>
