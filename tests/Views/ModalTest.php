<?php

use Flatpack\View\Components\Modal;

it('renders a modal component', function () {
    $title = 'Lorem ipsum';
    $modal = new Modal($title);
    $this->expect($modal->title)->toBe($title);
    $this->expect($modal->render())
         ->toBeInstanceOf(\Illuminate\Contracts\View\View::class);
});
