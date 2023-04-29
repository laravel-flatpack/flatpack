<?php

namespace Flatpack\Http\Livewire;

use Flatpack\Traits\WithComposition;
use Flatpack\Traits\WithFormFields;
use Flatpack\Traits\WithFormValidation;
use Livewire\Component;

class CreateRelation extends Component
{
    use WithFormFields;
    use WithFormValidation;
    use WithComposition;

    /**
     * Form fields values.
     *
     * @var array
     */
    public $relationFields = [];

    /**
     * Form fields binding property.
     *
     * @var string
     */
    public $fieldsBinding;

    /**
     * Form fields composition.
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
        $this->fieldsBinding = 'relationFields';

        $this->entry = new $this->model();

        foreach ($this->formFields as $key => $options) {
            $this->relationFields[$key] = null;
        }
    }

    public function render()
    {
        return view('flatpack::components.create-relation');
    }

    /**
     * Submit the form.
     */
    public function submit()
    {
        $this->clearErrors();

        $this->validateForm($this->relationFields, $this->onlyInputFields());

        $this->save();

        $this->clearForm();

        $this->emitUp('flatpack-relation:updated');

        $this->dispatchBrowserEvent('close-modal');
    }

    /**
     * Reset the form.
     */
    public function cancel()
    {
        $this->clearErrors();

        $this->clearForm();

        $this->dispatchBrowserEvent('close-modal');
    }

    /**
     * Create a new entity.
     *
     * @return void
     */
    private function save()
    {
        $model = $this->model;

        $this->entry = new $model();

        foreach ($this->formFields as $key => $options) {
            $this->entry->{$key} = $this->relationFields[$key] ?? null;
        }

        $this->entry->save();
    }

    private function clearErrors()
    {
        $this->formErrors = [];
    }

    private function clearForm()
    {
        $this->relationFields = [];
    }
}
