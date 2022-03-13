<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Faustoq\Flatpack\Traits\WithActions;
use Maxeckel\LivewireEditorjs\Http\Livewire\EditorJS;
use Illuminate\Support\Facades\Storage;
use Livewire\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class BlockEditor extends EditorJS
{
    use WithFileUploads;
    use WithActions;

    public $uploads = [];

    public $editorId;

    public $data;

    public $class;

    public $style;

    public $readOnly;

    public $placeholder;

    public $uploadDisk;

    public $downloadDisk;

    public $logLevel;

    public function mount(
        $editorId,
        $value = [],
        $class = '',
        $style = '',
        $readOnly = false,
        $placeholder = null,
        $uploadDisk = null,
        $downloadDisk = null
    ) {
        if (is_null($placeholder)) {
            $placeholder = config('livewire-editorjs.default_placeholder', 'Start typing...');
        }

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $this->editorId = $editorId;
        $this->data = $value;
        $this->class = $class;
        $this->style = $style;
        $this->readOnly = $readOnly;
        $this->placeholder = !empty($placeholder) ? $placeholder : __('Start typing...');
        $this->uploadDisk = config('flatpack.storage.disk', 'public');
        $this->downloadDisk = config('flatpack.storage.disk', 'public');

        $this->logLevel = config('livewire-editorjs.editorjs_log_level');
    }

    public function completedImageUpload(string $uploadedFileName, string $eventName, $fileName = null)
    {
        /** @var TemporaryUploadedFile $tmpFile */
        $tmpFile = collect($this->uploads)
            ->filter(function (TemporaryUploadedFile $item) use ($uploadedFileName) {
                return $item->getFilename() === $uploadedFileName;
            })
            ->first();

        // When no file name is passed, we use the hashName of the tmp file
        $storedFileName = $tmpFile->storeAs(
            '/',
            $fileName ?? $tmpFile->hashName(),
            $this->uploadDisk
        );

        $this->dispatchBrowserEvent($eventName, [
            'url' => Storage::disk($this->uploadDisk)->url($storedFileName),
        ]);
    }

    public function loadImageFromUrl(string $url)
    {
        $name = basename($url);
        $content = file_get_contents($url);

        Storage::disk($this->downloadDisk)->put($name, $content);

        return Storage::disk($this->downloadDisk)->url($name);
    }

    public function save()
    {
        $this->emitUp("flatpack-editor:save", $this->editorId, $this->data);
    }

    public function render()
    {
        return view('flatpack::components.block-editor');
    }
}
