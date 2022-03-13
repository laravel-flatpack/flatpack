<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Faustoq\Flatpack\Traits\WithComposition;
use Faustoq\Flatpack\Traits\WithFormFields;
use Faustoq\Flatpack\Traits\WithFormValidation;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CreateRelation extends Component
{
    use WithFormFields;
    use WithFormValidation;
    use WithComposition;

    public $fields = [];

    /**
     * Form fields.
     *
     * @var array
     */
    public $formFields = [];

    /**
     * Form field errors.
     *
     * @var array
     */
    public $formErrors = [];

    /**
     * Form composition.
     *
     * @var array
     */
    public $composition = [];

    /**
     * Model class name.
     *
     * @var string
     */
    public $model;

    /**
     * Entry instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $entry;

    public function mount()
    {
        $this->entry = new $this->model();

        $this->formFields = $this->getFormFields();

        foreach ($this->formFields as $key => $options) {
            $this->fields[$key] = null;
        }
    }

    public function render()
    {
        return view('flatpack::components.create-relation', [
            'main' => $this->getMainComposition(),
        ]);
    }

    public function create()
    {
        try {
            $this->formErrors = [];

            $this->validateForm($this->fields, $this->formFields);

            foreach ($this->formFields as $key => $options) {
                $this->entry->{$key} = $this->fields[$key] ?? null;
            }

            $this->entry->save();

            $this->fields = [];

            $this->emitUp('flatpack-relation:updated');
            $this->dispatchBrowserEvent('close-modal');
        } catch (ValidationException $e) {
            $this->formErrors = $e->errors();
        }
    }

    public function cancel()
    {
        $this->fields = [];

        $this->formErrors = [];

        $this->dispatchBrowserEvent('close-modal');
    }
}
