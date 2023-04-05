<?php

namespace Flatpack\View\Components;

use Flatpack\Facades\Flatpack;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
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
            'user' => Auth::user(),
        ]);
    }

    /**
     * Set the template with Flatpack composition.
     *
     * @return void
     */
    private function setTemplateComposition($template = [])
    {
        $layout = $this->customLayout();

        if (empty($layout)) {
            $layout = $this->defaultLayout();
        }

        $this->template = array_merge($template, $layout);
    }

    /**
     * Set navigation property.
     *
     * @return void
     */
    private function setNavigation($navigation)
    {
        $this->navigation = Arr::get($this->template, $navigation, []);
    }

    /**
     * Get the custom layout defined in flatpack root folder.
     *
     * @return array
     */
    private function customLayout()
    {
        try {
            $layout = Flatpack::getTemplateComposition('__global__', 'layout.yaml');
        } catch (\Exception $e) {
            $layout = [];
        }

        return $layout;
    }

    /**
     * Get the default layout.
     *
     * @return array
     */
    private function defaultLayout()
    {
        return [
            'sidebar' => collect(Flatpack::getComposition())
                ->filter(fn ($item, $key) => $key !== '__global__')
                ->map(fn ($item, $key) => [
                        "url" => route('flatpack.list', ['entity' => $key]),
                        "title" => Str::ucfirst($key),
                        "icon" => "folder",
                    ])
                ->toArray(),
        ];
    }
}
