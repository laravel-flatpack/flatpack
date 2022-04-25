<?php

use Flatpack\Traits\WithComposition;

beforeEach(function () {
    $this->trait = new class {
        use WithComposition {
            getMainComposition as public;
            getComposition as public;
            getAllCompositionFields as public;
        }

        public function __construct()
        {
            $this->composition = [
                'header' => [
                    'name' => [
                        'type' => 'heading',
                    ],
                ],
                'toolbar' => [
                    'save' => [
                        'type' => 'button',
                        'action' => 'save'
                    ],
                    'cancel' => [
                        'type' => 'button',
                        'action' => 'cancel'
                    ],
                ],
                'main' => [
                    'tabs' => [
                        'profile' => [
                            'name' => 'Profile',
                            'fields' => [
                                'name' => [
                                    'label' => 'Name',
                                    'type' => 'text',
                                    'placeholder' => 'Enter a user name',
                                    'rules' => 'required|string',
                                ],
                                'email' => [
                                    'label' => 'Email',
                                    'type' => 'email',
                                    'placeholder' => 'Email address',
                                    'rules' => 'required|email',
                                ],
                            ],
                        ],
                        'advanced' => [
                            'name' => 'Advanced',
                            'fields' => [
                                'bio' => [
                                    'label' => 'Bio',
                                    'type' => 'textarea',
                                ],
                            ],
                        ],
                    ],
                ],
                'sidebar' => [
                    'created_at' => [
                        'label' => 'Created at',
                        'type' => 'datetime-picker'
                    ],
                    'updated_at' => [
                        'label' => 'Updated at',
                        'type' => 'datetime-picker'
                    ],
                ]
            ];
        }
    };
});


it('returns the default form fields composition', function () {
    $trait = new class {
        public $composition = [];

        use WithComposition {
            getMainComposition as public;
        }

        public function __construct()
        {
            $this->composition = [
                'fields' => [
                    'name' => [
                        'label' => 'Name',
                        'placeholder' => 'Enter a user name',
                        'rules' => 'required|string',
                    ],
                    'email' => [
                        'label' => 'Email',
                        'type' => 'email',
                        'placeholder' => 'Email address',
                        'rules' => 'required|email',
                    ],
                    'bio' => [
                        'label' => 'Bio',
                        'type' => 'textarea',
                    ],
                ],
            ];
        }
    };

    $this->expect($trait->getMainComposition())->toBe([
        'name' => [
            'label' => 'Name',
            'placeholder' => 'Enter a user name',
            'rules' => 'required|string',
        ],
        'email' => [
            'label' => 'Email',
            'type' => 'email',
            'placeholder' => 'Email address',
            'rules' => 'required|email',
        ],
        'bio' => [
            'label' => 'Bio',
            'type' => 'textarea',
        ],
    ]);
});

it('returns the main form fields composition', function () {
    $this->expect($this->trait->getMainComposition())->toBe(
        $this->trait->composition['main']
    );
});

it('returns the header fields composition', function () {
    $this->expect($this->trait->getComposition('header'))->toBe([
        'name' => [
            'type' => 'heading',
        ],
    ]);
});

it('returns all form fields in a composition', function () {
    $this->expect($this->trait->getAllCompositionFields())->toBe([
        'name' => [
            'label' => 'Name',
            'type' => 'text',
            'placeholder' => 'Enter a user name',
            'rules' => 'required|string',
        ],
        'email' => [
            'label' => 'Email',
            'type' => 'email',
            'placeholder' => 'Email address',
            'rules' => 'required|email',
        ],
        'bio' => [
            'label' => 'Bio',
            'type' => 'textarea',
        ],
        'created_at' => [
            'label' => 'Created at',
            'type' => 'datetime-picker'
        ],
        'updated_at' => [
            'label' => 'Updated at',
            'type' => 'datetime-picker'
        ],
    ]);
});
