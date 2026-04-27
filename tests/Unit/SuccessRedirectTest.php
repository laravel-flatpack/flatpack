<?php

declare(strict_types=1);

use Flatpack\Support\SuccessRedirect;

test('fromFormSchema reads actions.save.success_redirect', function () {
    expect(SuccessRedirect::fromFormSchema([
        'actions' => [
            'save' => [
                'success_redirect' => 'create',
            ],
        ],
    ]))->toBe('create');

    expect(SuccessRedirect::fromFormSchema([
        'actions' => [
            'other' => ['action' => 'delete'],
        ],
    ]))->toBeNull();
});

test('successRedirectForFormSubmit uses YAML action key when form_action_id matches', function () {
    $schema = [
        'actions' => [
            'save' => [
                'action' => 'save',
                'label' => 'Save',
            ],
            'save_and_quit' => [
                'action' => 'save',
                'label' => 'Save and quit',
                'success_redirect' => 'list',
            ],
        ],
    ];

    expect(SuccessRedirect::successRedirectForFormSubmit($schema, 'save_and_quit', 'save'))->toBe('list');
    expect(SuccessRedirect::successRedirectForFormSubmit($schema, null, 'save'))->toBeNull();
    expect(SuccessRedirect::successRedirectForFormSubmit($schema, '', 'save'))->toBeNull();
});

test('normalize accepts allowed enum strings', function () {
    expect(SuccessRedirect::normalize(' list '))->toBe('list');
    expect(SuccessRedirect::normalize('edit'))->toBe('edit');
    expect(SuccessRedirect::normalize('stay'))->toBe('stay');
});

test('normalize treats YAML boolean true and string true as list', function () {
    expect(SuccessRedirect::normalize(true))->toBe('list');
    expect(SuccessRedirect::normalize('true'))->toBe('list');
    expect(SuccessRedirect::normalize('TRUE'))->toBe('list');
});

test('normalize rejects unknown or empty values', function () {
    expect(SuccessRedirect::normalize(false))->toBeNull();
    expect(SuccessRedirect::normalize('custom'))->toBeNull();
    expect(SuccessRedirect::normalize(''))->toBeNull();
    expect(SuccessRedirect::normalize(null))->toBeNull();
});

test('successRedirectForFormSubmit falls back to actions.save when keyed block omits redirect', function () {
    $schema = [
        'actions' => [
            'save' => [
                'action' => 'save',
                'success_redirect' => 'list',
            ],
            'save_secondary' => [
                'action' => 'save',
                'label' => 'Alt',
            ],
        ],
    ];

    expect(SuccessRedirect::successRedirectForFormSubmit($schema, 'save_secondary', 'save'))->toBe('list');
});

test('successRedirectForFormSubmit resolves boolean true on keyed header action', function () {
    $schema = [
        'actions' => [
            'publish' => [
                'action' => 'publish',
                'success_redirect' => true,
            ],
        ],
    ];

    expect(SuccessRedirect::successRedirectForFormSubmit($schema, 'publish', 'publish'))->toBe('list');
});
