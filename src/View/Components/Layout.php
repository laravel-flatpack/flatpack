<?php

namespace Flatpack\View\Components;

use Flatpack\Facades\Flatpack;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class Layout extends Component
{
    public $template = [];

    public $navigation = [];

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->setTemplateComposition();
        $this->setNavigation('sidebar');
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|\Closure|string
     */
    public function render()
    {
        $current = request()->flatpack['entity'] ?? null;

        return view('flatpack::layouts.layout', [
            'current' => (empty($current) ? 'home' : $current),
            'navigation' => $this->navigation,
            'user' => auth()->user(),
        ]);
    }

    /**
     * Set the template with Flatpack composition.
     *
     * @return void
     */
    private function setTemplateComposition($template = [])
    {
        $options = request()->flatpack['options'] ?? [];
        $layout = empty($options) ? $this->defaultLayout() : $options;

        $this->template = array_merge($template, [
            'sidebar' => $layout,
        ]);
    }

    /**
     * Set navigation property.
     *
     * @return void
     */
    private function setNavigation($navigation)
    {
        $this->navigation = collect(config('flatpack.navigation', []))
            ->merge(data_get($this->template, $navigation, []))
            ->sortBy('order')
            ->toArray();
    }

    /**
     * Get the default layout.
     *
     * @return array
     */
    private function defaultLayout()
    {
        return collect(Flatpack::getComposition())
            ->map(fn ($item, $key) => [
                    "url" => route('flatpack.list', ['entity' => $key]),
                    "title" => Str::ucfirst($key),
                    "icon" => "folder",
                ])
            ->toArray();
    }
}
