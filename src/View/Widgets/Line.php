<?php

namespace Flatpack\View\Widgets;

use Illuminate\View\Component;

class Line extends Component
{
    public $dataset = [];

    public $comparedTotal = 0;

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
        return view('flatpack::components.dashboard.line', [
            'heading' => $this->getHeading(),
            'data' => $this->getDataset(),
            'value' => $this->getDisplayValue(),
            'increase' => $this->getPercentageIncrease(),
            'id' => uniqid(),
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

    public function getDisplayValue()
    {
        return number_format($this->getTotal());
    }

    public function getComparedTotal()
    {
        return $this->comparedTotal;
    }

    public function getTotal()
    {
        return collect($this->getDataset())->reduce(fn ($prev, $item) => $prev + $item, 0);
    }

    /**
     * Calculate the percentage of increase.
     *
     * @return int
     */
    protected function getPercentageIncrease()
    {
        $now = $this->getTotal();

        $previous = $this->getComparedTotal();

        if ($previous === 0) {
            return 100;
        }

        return round((($now - $previous) / $previous) * 100, 1);
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

    protected function setComparedTotal($value)
    {
        $this->comparedTotal = intval($value);

        return $this;
    }
}
