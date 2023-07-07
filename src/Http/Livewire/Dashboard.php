<?php

namespace Flatpack\Http\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public $widgets = [];

    public function mount()
    {
        $widgets = config('flatpack.dashboard', []);

        $this->widgets = collect($widgets)
            ->map(fn ($widget) => new $widget())
            ->toArray();
    }

    /**
     * Render component.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('flatpack::components.dashboard');
    }
}
