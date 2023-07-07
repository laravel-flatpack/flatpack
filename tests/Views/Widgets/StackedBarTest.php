<?php

use Flatpack\View\Widgets\StackedBar;

it('creates a stacked bar widget with the given options', function () {
    $widget = new class () extends StackedBar {
        public function configure(): void
        {
            $this->setHeading('Total views')
                ->setDataset([
                    'desktop' => [
                        'value' => 1090,
                    ],
                    'tablet' => [
                        'value' => 250,
                    ],
                    'mobile' => [
                        'value' => 2000,
                    ],
                ]);
        }
    };

    $view = $widget->render();
    $this->expect($view)->toBeInstanceOf(\Illuminate\Contracts\View\View::class);

    $html = $view->render();
    $this->assertStringContainsString('Total views', $html);
    $this->assertStringContainsString('3,340', $html);
});

it('creates a stacked bar widget without options', function () {
    $widget = new class () extends StackedBar {};

    $this->expect($widget->render())->toBeInstanceOf(\Illuminate\Contracts\View\View::class);
});
