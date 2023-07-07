<?php

use Flatpack\Http\Livewire\Columns\Column;
use Flatpack\Tests\Models\Post;
use Flatpack\View\Widgets\Table;

it('creates a table widget with the given options', function () {
    Post::factory()->count(5)->create();

    $widget = new class () extends Table {
        public function configure(): void
        {
            $data = Post::orderBy('created_at', 'desc')->get();

            $this->setHeading('Drafts')
                ->setDataset($data);
        }

        public function getColumns()
        {
            return [
                Column::make('Title', 'title'),
                Column::make('Created', 'created_at')
                    ->format(fn ($value) => $value->diffForHumans(['short' => true])),
            ];
        }
    };

    $view = $widget->render();
    $this->expect($view)->toBeInstanceOf(\Illuminate\Contracts\View\View::class);

    $html = $view->render();
    $this->assertStringContainsString('Drafts', $html);
    $this->assertStringContainsString('Title', $html);
    $this->assertStringContainsString('Created', $html);
});

it('creates a table widget without options', function () {
    $widget = new class () extends Table {};

    $this->expect($widget->render())->toBeInstanceOf(\Illuminate\Contracts\View\View::class);
});
