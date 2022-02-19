<?php

namespace Faustoq\Flatpack\View\Components;

use Illuminate\Support\Arr;
use Illuminate\View\Component;

class ActionButton extends Component
{
    /**
     * Action name.
     *
     * @var string
     */
    public $action;

    /**
     * Button text.
     *
     * @var string
     */
    public $label;

    /**
     * Button visibility.
     *
     * @var bool
     */
    public $hidden;

    /**
     * Button primary style.
     *
     * @var bool
     */
    public $primary;

    /**
     * Button options.
     *
     * @var array
     */
    public $options;

    /**
     * ActionButton constructor.
     *
     * @param array $options
     * @return void
     */
    public function __construct($options = [])
    {
        $this->options = $options;

        $this->initProps();
    }

    /**
      * Get the view / contents that represent the component.
      *
      * @return \Illuminate\View\View|\Closure|string
      */
    public function render()
    {
        return view('flatpack::components.action-button');
    }

    /**
     * Dynamically initialize component props based on the options.
     */
    private function initProps(): void
    {
        $this->action = $this->getOption('action');
        $this->label = $this->getOption('label', '');
        $this->hidden = $this->getOption('hidden', false);
        $this->primary = $this->getOption('primary', false);
    }

    /**
     * Get option value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getOption($key, $default = null)
    {
        return Arr::get($this->options, $key, $default);
    }
}
