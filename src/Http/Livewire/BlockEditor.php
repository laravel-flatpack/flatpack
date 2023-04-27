<?php

namespace Flatpack\Http\Livewire;

use Flatpack\Traits\WithActions;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Maxeckel\LivewireEditorjs\Http\Livewire\EditorJS;

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

    public $uploadPath;

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
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $this->editorId = $editorId;
        $this->data = $value;
        $this->class = $class;
        $this->style = $style;
        $this->readOnly = $readOnly;
        $this->placeholder = ! empty($placeholder) ? $placeholder : __('Start typing...');
        $this->uploadDisk = config('flatpack.storage.disk', 'public');
        $this->downloadDisk = config('flatpack.storage.disk', 'public');
        $this->uploadPath = config('flatpack.storage.path', 'uploads');
        $this->logLevel = "ERROR";
    }

    /**
     * Upload image from url.
     *
     * @param string $url
     * @return mixed
     */
    public function loadImageFromUrl(string $url)
    {
        $name = basename($url);
        $content = file_get_contents($url);

        Storage::disk($this->downloadDisk)->put($name, $content);

        return  optional(Storage::disk($this->downloadDisk))->url($name);
    }

    /**
     * Emit save event to parent components.
     *
     * @return void
     */
    public function save()
    {
        $this->emitUp("flatpack-editor:save", $this->editorId, $this->data);
    }

    /**
     * Render component.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('flatpack::components.block-editor');
    }
}
