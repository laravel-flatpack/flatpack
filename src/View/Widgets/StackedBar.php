<?php

namespace Flatpack\View\Widgets;

use Illuminate\View\Component;

class StackedBar extends Component
{
    public $dataset = [];

    public $heading;

    public function __construct()
    {
        $this->configure();
    }

    public function configure(): void
    {
        $this->setHeading(null);
        $this->setDataset([]);
    }

    public function render()
    {
        return view('flatpack::components.dashboard.stacked-bar', [
            'heading' => $this->getHeading(),
            'data' => $this->getDataset(),
            'total' => $this->getTotal(),
            'percentage' => function ($value) {
                return round(($value / $this->getTotal()) * 100);
            },
        ]);
    }

    public function getHeading()
    {
        return $this->heading;
    }

    public function getDataset()
    {
        return $this->dataset;
    }

    public function getTotal()
    {
        return collect($this->getDataset())->reduce(
            fn ($prev, $item) => $prev + data_get($item, 'value', 0),
            0
        );
    }

    protected function setHeading($heading)
    {
        $this->heading = $heading;

        return $this;
    }

    protected function setDataset($data)
    {
        $this->dataset = $data;

        return $this;
    }
}
