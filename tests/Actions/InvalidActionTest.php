<?php

use Flatpack\Actions\FlatpackAction;

it('should not handle the action', function () {
    $action = new class () extends FlatpackAction {
        public function __construct()
        {
            parent::__construct('posts', \Flatpack\Tests\Models\Post::class);
        }
    };

    $this->expectException(\Exception::class);

    $action->handle();
});

it('should not authorize the action', function () {
    $action = new class () extends FlatpackAction {
        public function __construct()
        {
            parent::__construct('posts', \Flatpack\Tests\Models\Post::class);
        }
    };

    $this->expectException(\Exception::class);

    $action->authorize();
});
