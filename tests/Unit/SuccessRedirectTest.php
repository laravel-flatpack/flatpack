<?php

declare(strict_types=1);

use Flatpack\Support\SuccessRedirect;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

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
    expect(SuccessRedirect::normalize(99))->toBeNull();
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

test('successRedirectForFormSubmit walks action blocks when submitted handler matches', function () {
    $schema = [
        'actions' => [
            'archive' => [
                'action' => 'archive',
                'success_redirect' => 'edit',
            ],
        ],
    ];

    expect(SuccessRedirect::successRedirectForFormSubmit($schema, null, 'archive'))->toBe('edit');
});

test('successRedirectForFormSubmit returns null when submitted action name is blank', function () {
    expect(SuccessRedirect::successRedirectForFormSubmit(['actions' => []], null, '   '))->toBeNull();
});

test('responseForFormSave returns list create edit and stay routes', function () {
    expect(SuccessRedirect::responseForFormSave('list', 'posts', true, '1')->getTargetUrl())
        ->toContain('posts');
    expect(SuccessRedirect::responseForFormSave('create', 'posts', true, '1')->getTargetUrl())
        ->toContain('posts/create');
    expect(SuccessRedirect::responseForFormSave('edit', 'posts', false, '7')->getTargetUrl())
        ->toContain('posts/7');
    expect(SuccessRedirect::responseForFormSave('current', 'posts', false, '7')->getTargetUrl())
        ->toContain('posts/7');
    expect(SuccessRedirect::responseForFormSave('unknown', 'posts', false, '7')->getTargetUrl())
        ->toContain('posts/7');
});

test('responseForEntityAction returns list index or edit based on target and record', function () {
    expect(SuccessRedirect::responseForEntityAction('list', 'posts', null)->getTargetUrl())
        ->toContain('posts');
    expect(SuccessRedirect::responseForEntityAction('edit', 'posts', '3')->getTargetUrl())
        ->toContain('posts/3');
    expect(SuccessRedirect::responseForEntityAction('edit', 'posts', null)->getTargetUrl())
        ->toContain('posts');
    expect(SuccessRedirect::responseForEntityAction('current', 'posts', '2')->getTargetUrl())
        ->toContain('posts/2');
    expect(SuccessRedirect::responseForEntityAction('current', 'posts', null)->isRedirect())->toBeTrue();
    expect(SuccessRedirect::responseForEntityAction('nope', 'posts', '1')->isRedirect())->toBeTrue();
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
