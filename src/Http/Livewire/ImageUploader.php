<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Faustoq\Flatpack\Traits\WithActions;
use Faustoq\Flatpack\Traits\WithStorageFiles;
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
    * Livewire component listeners.
    */
    protected $listeners = [
        'flatpack-imageuploader:save' => 'handleUpload',
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

        foreach ($this->previousValue as $image) {
            $name = $this->imageName($image);
            $this->images[$name] = $image;
        }

        $this->previousValue = Arr::wrap($entry->{$name});
        $this->size = Arr::get($options, 'maxSize', 2048);
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
            $this->images[$image->hashName()] = $image;
        }

        $this->rawImages = [];

        return $this->stateUpdated();
    }

    /**
     * Upload images.
     *
     * @param array $entry Serialized array of the model instance.
     * @return array
     */
    public function handleUpload($entry)
    {
        $this->entry = $this->model::find($entry['id']);

        return $this->uploadFiles();
    }

    public function uploadFiles()
    {
        $files = [];

        try {
            $uploadAction = $this->getAction('upload')
                ->setEntry($this->entry)
                ->addFiles($this->images);

            $files = $uploadAction->run();
        } catch (\Exception $e) {
            $this->notifyError($e->getMessage());
        }

        return $files;
    }

    private function imageName($url)
    {
        return last(explode('/', $url));
    }

    /**
     * Remove uploaded image.
     *
     * @param  string $image
     * @return void
     */
    public function handleRemoveImage($image)
    {
        if ($this->images[$image] instanceof \Livewire\TemporaryUploadedFile) {
            $this->images[$image]->delete();
        } else {
            $this->removeFile($image);
        }

        unset($this->images[$image]);

        $this->emitUp('flatpack-imageuploader:remove', $image);

        return $this->stateUpdated();
    }

    /**
     * Get the images.
     *
     * @return array
     */
    public function getCurrentValue()
    {
        return array_values($this->images);
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
        $this->emit('flatpack-imageuploader:updated', $this->getCurrentValue());
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
        return view('flatpack::components.image-uploader');
    }
}
