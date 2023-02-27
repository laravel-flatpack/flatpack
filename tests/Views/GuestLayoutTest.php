<?php

use Flatpack\View\Components\GuestLayout;

it('renders guest layout', function () {
    $layout = new GuestLayout();

    $this->expect($layout->render())->toBeInstanceOf(\Illuminate\Contracts\View\View::class);
});
