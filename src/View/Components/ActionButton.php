<?php

namespace Flatpack\View\Components;

use Flatpack\Traits\WithActions;
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
     * Destination link.
     *
     * @var string
     */
    public $link = "#";

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
     * @var string
     */
    public $style = "";

    /**
     * Shortcut key combined with cmd/ctrl key.
     *
     * @var mixed
     */
    public $shortcut = false;

    /**
     * ActionButton constructor.
     *
     * @param string $entity - Entity name
     * @param string $model - Model class name
     * @param array $options - Button options
     * @param string $style - Button style
     * @param string $method - Action method name
     * @return void
     */
    public function __construct(
        $key,
        $entity,
        $model,
        $options = [],
        $style = "",
        $method = "action"
    ) {
        $this->key = $key;
        $this->entity = $entity;
        $this->model = $model;
        $this->options = $options;
        $this->method = $method;
        $this->style = $style;

        $this->initProps();
    }

    /**
      * Get the view / contents that represent the component.
      *
      * @return \Illuminate\View\View|\Closure|string
      */
    public function render()
    {
        if ($this->method === "bulkAction") {
            return view("flatpack::components.action-button-bulk");
        }

        return view("flatpack::components.action-button");
    }

    /**
     * Dynamically initialize component props based on the options.
     *
     * @return void
     */
    private function initProps(): void
    {
        $this->action = $this->getOption("action");
        $this->link = $this->formatLink($this->getOption("link", "#"));
        $this->label = $this->getOption("label", "");
        $this->shortcut = $this->getOption("shortcut", false);
        $this->confirm = $this->getOption("confirm", false);
        $this->hidden = $this->getOption("hidden", false);
        $this->style = $this->getOption("style", $this->style, [
            "primary",
            "secondary",
            "info",
            "success",
            "warning",
            "danger",
        ]);

        $this->setConfirmationMessage();
    }

    /**
     * Format link, returns flatpack form route or absolute url.
     *
     * @return void
     */
    private function formatLink($link)
    {
        return (str_contains($link, "?") || str_contains($link, "#") || str_contains($link, "/")) ?
            $link : route("flatpack.form", [
                "entity" => $this->entity,
                "id" => $link,
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
    private function getOption($key, $default = null, $acceptedValues = "*")
    {
        $option = Arr::get($this->options, $key, $default);

        if ($acceptedValues !== "*") {
            return in_array(
                $option,
                collect($acceptedValues)->toArray()
            ) ? $option : $default;
        }

        return $option;
    }

    /**
     * Set the default confirmation message to be displayed before action.
     *
     * @return void
     */
    private function setConfirmationMessage()
    {
        $this->confirmationMessage = $this->getOption(
            "confirmationMessage",
            "Are you sure you want to proceed?"
        );

        try {
            $actionInstance = $this->getAction($this->action);

            if ($this->method === "bulkAction") {
                $actionInstance->setIsMultiple(true);
            }

            if (method_exists($actionInstance, "getConfirmationMessage")) {
                $this->confirmationMessage = $actionInstance->getConfirmationMessage();
            }
        } catch (\Exception $e) {
            //
        }

        $this->options = [
            ...$this->options,
            "confirmationMessage" => $this->confirmationMessage,
        ];
    }
}
