<?php

namespace Faustoq\Flatpack\View\Components;

use Faustoq\Flatpack\Traits\WithActions;
use Illuminate\Support\Arr;
use Illuminate\View\Component;

class ActionButton extends Component
{
    use WithActions;

    /**
     * Action name.
     *
     * @var string
     */
    public $action;

    /**
     * Requires action confirmation.
     *
     * @var bool
     */
    public $confirm;

    /**
     * Confirmation message.
     *
     * @var string
     */
    public $confirmationMessage;

    /**
     * Entity name.
     *
     * @var string
     */
    public $entity;

    /**
     * Button visibility.
     *
     * @var bool
     */
    public $hidden;

    /**
     * Button text.
     *
     * @var string
     */
    public $label;

    /**
     * Button key.
     *
     * @var string
     */
    public $key;

    /**
     * Method to call on click.
     *
     * @var string
     */
    public $method;

    /**
     * Model class name.
     *
     * @var string
     */
    public $model;

    /**
     * Button options.
     *
     * @var array
     */
    public $options;

    /**
     * Button style.
     *
     * @var bool
     */
    public $style;

    /**
     * ActionButton constructor.
     *
     * @param string $entity - Entity name
     * @param string $model - Model class name
     * @param array $options - Button options
     * @return void
     */
    public function __construct($key, $entity, $model, $options = [], $method = 'action')
    {
        $this->key = $key;
        $this->entity = $entity;
        $this->model = $model;
        $this->options = $options;
        $this->method = $method;
        $this->confirmationMessage = 'Are you sure you want to proceed?';

        $this->initProps();
    }

    /**
      * Get the view / contents that represent the component.
      *
      * @return \Illuminate\View\View|\Closure|string
      */
    public function render()
    {
        $this->setConfirmationMessage();

        if ($this->method === 'bulkAction') {
            return view('flatpack::components.action-button-bulk');
        }

        return view('flatpack::components.action-button');
    }

    /**
     * Dynamically initialize component props based on the options.
     */
    private function initProps(): void
    {
        $this->action = $this->getOption('action');
        $this->label = $this->getOption('label', '');
        $this->confirm = $this->getOption('confirm', false);
        $this->hidden = $this->getOption('hidden', false);
        $this->style = $this->getOption('style', '', [
            'primary',
            'secondary',
            'info',
            'success',
            'warning',
            'danger',
        ]);
    }

    /**
     * Get option value.
     *
     * @param string $key
     * @param mixed $default
     * @param mixed $acceptedValues
     * @return mixed
     */
    private function getOption($key, $default = null, $acceptedValues = '*')
    {
        $option = Arr::get($this->options, $key, $default);

        if ($acceptedValues !== '*') {
            return in_array(
                $option,
                collect($acceptedValues)->toArray()
            ) ? $option : $default;
        }

        return $option;
    }

    private function setConfirmationMessage()
    {
        try {
            $action = $this->getAction($this->action);

            if ($this->method === 'bulkAction' && method_exists($action, 'getBulkConfirmationMessage')) {
                $this->confirmationMessage = $action->getBulkConfirmationMessage();
            } elseif (method_exists($action, 'getConfirmationMessage')) {
                $this->confirmationMessage = $action->getConfirmationMessage();
            }
        } catch (\Exception $e) {
            //
        }
    }
}
