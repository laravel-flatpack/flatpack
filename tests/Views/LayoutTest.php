<?php

use Flatpack\View\Components\Layout;

it('renders layout', function () {
    $layout = new Layout();

    $this->expect($layout->render())->toBeInstanceOf(\Illuminate\Contracts\View\View::class);
});
