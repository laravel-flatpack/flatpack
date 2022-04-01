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

        $this->formFields = $this->getMainComposition();

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

    /**
     * Submit the form.
     */
    public function submit()
    {
        $this->clearErrors();

        $this->validateForm($this->fields, $this->formFields);

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
            $this->entry->{$key} = $this->fields[$key] ?? null;
        }

        $this->entry->save();
    }

    private function clearErrors()
    {
        $this->formErrors = [];
    }

    private function clearForm()
    {
        $this->fields = [];
    }
}
