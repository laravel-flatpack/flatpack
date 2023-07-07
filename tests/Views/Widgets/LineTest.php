<?php

use Flatpack\View\Widgets\Line;

it('creates a line widget with the given options', function () {
    $widget = new class () extends Line {
        public function configure(): void
        {
            $this->setHeading('Unique Views')
                ->setDataset([101, 215, 236, 157, 223, 259, 316])
                ->setComparedTotal(1204);
        }
    };

    $view = $widget->render();
    $this->expect($view)->toBeInstanceOf(\Illuminate\Contracts\View\View::class);

    $html = $view->render();
    $this->assertStringContainsString('Unique Views', $html);
    $this->assertStringContainsString('25.2%', $html);
});

it('creates a line widget without options', function () {
    $widget = new class () extends Line {};

    $this->assertStringContainsString('100%', $widget->render()->render());
});
