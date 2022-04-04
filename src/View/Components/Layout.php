<?php

namespace Flatpack\View\Components;

use Flatpack\Facades\Flatpack;
use Illuminate\Support\Arr;
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
        ]);
    }

    /**
     * Set the template with Flatpack composition.
     *
     * @return void
     */
    private function setTemplateComposition()
    {
        try {
            $layout = Flatpack::getTemplateComposition('__global__', 'layout.yaml');
        } catch (\Exception $e) {
            $layout = [];
        }

        if (empty($layout)) {
            $layout = $this->defaultLayout();
        }

        $this->template = array_merge($this->template, $layout);
    }

    private function setNavigation($navigation)
    {
        $this->navigation = Arr::get($this->template, $navigation, []);
    }

    /**
     * Get the default layout.
     *
     * @return array
     */
    private function defaultLayout()
    {
        $composition = Flatpack::getComposition();
        $layout = [
            'sidebar' => collect($composition)
                ->filter(function ($item, $key) {
                    return $key !== '__global__';
                })
                ->map(function ($item, $key) {
                    return [
                        "url" => route('flatpack.list', ['entity' => $key]),
                        "title" => Str::ucfirst($key),
                        "icon" => "folder",
                    ];
                })
                ->toArray(),
        ];

        return $layout;
    }
}
