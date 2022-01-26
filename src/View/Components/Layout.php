<?php

namespace Faustoq\Flatpack\View\Components;

use Illuminate\View\Component;

class Layout extends Component
{
    public $navigation;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->navigation = [
            'home' => [
                'title' => 'Home',
                'url' => route('flatpack.home'),
                'icon' => 'grid_view'
            ],
            'new' => [
                'title' => 'Create new post',
                'url' => route('flatpack.form', ['entity' => 'posts', 'id' => 'create']),
                'type' => 'button',
                'icon' => 'add_box'
            ],
            'posts' => [
                'title' => 'Posts',
                'url' => route('flatpack.list', ['entity' => 'posts']),
                'icon' => 'push_pin'
            ],
            'categories' => [
                'title' => 'Categories',
                'url' => route('flatpack.list', ['entity' => 'categories']),
                'icon' => 'label'
            ],
            'tags' => [
                'title' => 'Tags',
                'url' => route('flatpack.list', ['entity' => 'tags']),
                'icon' => 'tag'
            ],
        ];
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|\Closure|string
     */
    public function render()
    {
        return view('flatpack::components.layout');
    }
}
