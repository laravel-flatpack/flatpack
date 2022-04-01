<?php

namespace Flatpack\Http\Livewire;

use Flatpack\Traits\WithActions;
use Flatpack\Traits\WithStorageFiles;
use Illuminate\Support\Arr;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImageUploader extends Component
{
    use WithFileUploads;
    use WithActions;
    use WithStorageFiles;

    /**
     * Entity name.
     *
     * @var string
     */
    public $entity;

    /**
     * Model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $entry;

    /**
     * Model class name.
     *
     * @var string
     */
    public $model;

    /**
     * Initial values.
     *
     * @var array
     */
    public $previousValue = [];

    /**
     * Raw Images from input.
     *
     * @var array
     */
    public $rawImages = [];

    /**
     * Uploaded images.
     *
     * @var array
     */
    public $images = [];

    /**
     * Preview mode.
     *
     * @var string
     */
    public $preview = 'auto';

    /**
     * Multiple mode.
     *
     * @var bool
     */
    public $multiple;

    /**
     * Field name.
     *
     * @var string
     */
    public $name;

    /**
     * Max allowed image size in kb.
     *
     * @var int
     */
    public $size;

    /**
     * List of images to be deleted on save.
     *
     * @var array
     */
    public $toDelete = [];

    /**
    * Livewire component listeners.
    */
    protected $listeners = [
        'flatpack-form:saved' => 'handleUpload',
    ];

    /**
     * Validation messages.
     *
     * @var array
     */
    protected $messages = [
        'rawImages.*.image' => 'Only images are allowed.',
        'rawImages.*.mimes' => 'The image format must be :mimes.',
        'rawImages.*.max' => 'Images must not be greater than :max KB.',
    ];

    public function mount(
        string $name,
        string $entity,
        string $model,
        \Illuminate\Database\Eloquent\Model $entry,
        $options = [],
    ) {
        $this->name = $name;
        $this->entity = $entity;
        $this->model = $model;
        $this->entry = $entry;
        $this->rawImages = [];
        $this->images = [];

        $this->previousValue = Arr::wrap($entry->{$name});

        foreach ($this->previousValue as $image) {
            array_push($this->images, $image);
        }

        $this->size = Arr::get($options, 'maxSize', 5120);
        $this->multiple = Arr::get($options, 'multiple', false);
        $this->preview = Arr::get($options, 'preview', 'auto');
    }

    public function updatingRawImages()
    {
        $this->rawImages = [];

        if (! $this->multiple) {
            $this->images = [];
        }
    }

    public function updatedRawImages($value)
    {
        $this->validate([
            'rawImages.*' => "image|mimes:jpeg,png,jpg,gif,svg|max:{$this->size}",
        ]);

        $this->prepareImages();
    }

    /**
     * Images temporary upload.
     *
     * @return void
     */
    public function prepareImages()
    {
        foreach ($this->rawImages as $image) {
            array_push($this->images, $image);
        }

        $this->rawImages = [];

        return $this->stateUpdated();
    }

    /**
     * After the form is saved,
     * refresh local entry instance and upload images.
     *
     * @param array $fields Form fields
     * @param mixed $id Entry id
     * @return void
     */
    public function handleUpload($fields, $id)
    {
        $this->deleteOldFiles();

        $this->entry = $this->model::find($id);

        $this->images = $this->uploadFiles();

        $this->entry->{$this->name} = $this->getCurrentValue();

        $this->entry->save();

        return $this->stateUpdated();
    }

    /**
     * Upload images.
     *
     * @return array
     */
    public function uploadFiles()
    {
        try {
            if (count($this->onlyFilesToUpload($this->images)) > 0) {
                $images = $this->getAction('upload')
                    ->setEntry($this->entry)
                    ->addFiles($this->images)
                    ->run();

                $this->images = $images;
            }
        } catch (\Exception $e) {
            $this->notifyError($e->getMessage());
        }

        return $this->images;
    }

    /**
     * Remove uploaded image.
     *
     * @param  string $image
     * @return void
     */
    public function handleRemoveImage($index)
    {
        if ($this->images[$index] instanceof \Livewire\TemporaryUploadedFile) {
            $this->images[$index]->delete();
        } else {
            $this->toDelete[] = $this->images[$index];
        }

        unset($this->images[$index]);

        return $this->stateUpdated();
    }

    /**
     * Get the images.
     *
     * @return array
     */
    public function getCurrentValue()
    {
        $value = $this->onlyFilesAlreadyUploaded($this->images);

        if ($this->multiple) {
            return $value;
        }

        return collect($value)->first();
    }

    /**
     * Get original images value.
     *
     * @return array
     */
    public function getPreviousValue()
    {
        return array_values($this->previousValue);
    }

    /**
     * Notify when images state is updated.
     *
     * @return void
     */
    public function stateUpdated()
    {
        $this->emit('flatpack-imageuploader:updated', $this->name, $this->getCurrentValue());
    }

    /**
     * Notify error.
     *
     * @return void
     */
    public function notifyError($message)
    {
        $this->emit('flatpack-imageuploader:error', $message, $this->getCurrentValue());
    }

    public function render()
    {
        $this->cleanUp();

        return view('flatpack::components.image-uploader');
    }

    /**
     * Remove input files if validation failed.
     *
     * @return void
     */
    private function cleanUp()
    {
        if ($this->getErrorBag()->has('rawImages.*')) {
            $this->rawImages = [];
        }
    }

    /**
     * Delete files from storage.
     *
     * @return void
     */
    private function deleteOldFiles()
    {
        foreach ($this->toDelete as $file) {
            $this->removeFile($file);
        }

        $this->toDelete = [];
    }
}
