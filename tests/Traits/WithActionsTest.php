<?php

use Flatpack\Traits\WithActions;

beforeEach(function () {
    $this->trait = new class () {
        use WithActions {
            getAction as public;
        }

        public function __construct()
        {
            $this->entity = 'posts';
            $this->model = \Flatpack\Tests\Models\Post::class;
        }
    };
});

it('returns a valid action instance', function () {
    $this->expect($this->trait->getAction('save'))
        ->toBeInstanceOf(\Flatpack\Actions\FlatpackAction::class)
        ->toBeInstanceOf(config('flatpack.actions.save'));
});

it('should throw an error if the action is not found', function () {
    $this->expectException(\Flatpack\Exceptions\ActionNotFoundException::class);
    $this->trait->getAction('non-existing-action');
});

it('should throw an error if the action references a non existing class', function () {
    config(['flatpack.actions.my-action' => 'NonExistingClass']);

    $this->expectException(\Flatpack\Exceptions\ActionNotFoundException::class);

    $this->trait->getAction('my-action');
});

it('should throw an error if the action references a not compatible class', function () {
    $wrongAction = new class () {
    };

    config(['flatpack.actions.my-action' => $wrongAction::class]);

    $this->expect(fn () => $this->trait->getAction('my-action'))
         ->toThrow(Exception::class, "Action class must implement \Flatpack\Contracts\FlatpackAction interface");
});


it('should throw an error if the action references a not allowed class', function () {
    $wrongAction = new class () implements \Flatpack\Contracts\FlatpackAction {
        public function authorize()
        {
            return true;
        }

        public function handle()
        {
            //
        }
    };

    config(['flatpack.actions.my-action' => $wrongAction::class]);

    $this->expect(fn () => $this->trait->getAction('my-action'))
         ->toThrow(Exception::class, "Action class must extend \Flatpack\Actions\FlatpackAction");
});
