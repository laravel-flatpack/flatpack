<?php

namespace Faustoq\Flatpack\View\Components;

use Illuminate\View\Component;

class Modal extends Component
{
    /**
     * Modal Title
     *
     * @var string
     */
    public $title;

    public function __construct($title)
    {
        $this->title = $title;
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('flatpack::components.modal');
    }
}
