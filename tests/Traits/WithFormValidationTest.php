<?php

use Flatpack\Traits\WithFormValidation;

it('returns form validation rules', function () {
    $trait = new class {
        use WithFormValidation {
            getValidationRules as public;
        }
    };

    $this->expect(
        $trait->getValidationRules([
            'name' => [
                'rules' => 'required|string',
            ],
            'email' => [
                'rules' => 'required|email',
            ],
            'bio' => []
        ])
    )->toBe([
        'name' => 'required|string',
        'email' => 'required|email',
        'bio' => 'present',
    ]);
});


it('should pass form validation', function () {
    $trait = new class {
        use WithFormValidation {
            validateForm as public;
        }
    };

    $data = [
        'name' => 'User name',
        'email' => 'user@example.com',
        'bio' => 'Some bio'
    ];

    $this->expect(
        $trait->validateForm($data, [
            'name' => [
                'rules' => 'required|string',
            ],
            'email' => [
                'rules' => 'required|email',
            ],
            'bio' => []
        ])
    )->toBe($data);
});

it('should fail form validation', function () {
    $trait = new class {
        use WithFormValidation {
            validateForm as public;
        }
    };

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $trait->validateForm([
        'name' => 'User name',
        'email' => 'user@example.com',
    ], [
        'name' => [
            'rules' => 'required|string',
        ],
        'email' => [
            'rules' => 'required|email',
        ],
        'bio' => []
    ]);
});
