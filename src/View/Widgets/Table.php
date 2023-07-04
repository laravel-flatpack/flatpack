<?php

namespace Flatpack\View\Widgets;

use Illuminate\View\Component;

class Table extends Component
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
        return view('flatpack::components.dashboard.table', [
            'heading' => $this->getHeading(),
            'columns' => $this->getColumns(),
            'data' => $this->getDataset(),
        ]);
    }

    public function getDataset()
    {
        return $this->dataset;
    }

    public function getHeading()
    {
        return $this->heading;
    }

    public function getColumns()
    {
        return [];
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
