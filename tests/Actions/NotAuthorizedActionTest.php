<?php

use Flatpack\Actions\FlatpackAction;
use Flatpack\Contracts\FlatpackAction as FlatpackActionContract;

it('should not authorize the action', function () {
    $action = new class () extends FlatpackAction implements FlatpackActionContract {
        public function __construct()
        {
            parent::__construct('posts', \Flatpack\Tests\Models\Post::class);
        }

        public function authorize()
        {
            return false;
        }

        public function handle()
        {
            //
        }
    };

    $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

    $action->run();
});
