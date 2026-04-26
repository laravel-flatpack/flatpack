<?php

declare(strict_types=1);

use Flatpack\Schema\HeaderActions;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

test('fromSchema keeps literal href values for header actions', function () {
    $actions = HeaderActions::fromSchema([
        'actions' => [
            'create' => [
                'label' => 'Create',
                'href' => '/posts/create',
            ],
        ],
    ]);

    expect($actions)->toHaveCount(1);
    expect($actions[0])->toMatchArray([
        'id' => 'create',
        'label' => 'Create',
        'href' => '/posts/create',
        'variant' => 'outline',
    ]);
});

test('fromSchema passes through allowed variant', function () {
    $actions = HeaderActions::fromSchema([
        'actions' => [
            'create' => [
                'label' => 'Create',
                'href' => '/posts/create',
                'variant' => 'outline',
            ],
        ],
    ]);

    expect($actions[0]['variant'])->toBe('outline');
});

test('fromSchema defaults invalid variant to outline', function () {
    $actions = HeaderActions::fromSchema([
        'actions' => [
            'create' => [
                'label' => 'Create',
                'href' => '/posts/create',
                'variant' => 'not-a-real-variant',
            ],
        ],
    ]);

    expect($actions[0]['variant'])->toBe('outline');
});

test('fromSchema sets primary flag for variant primary and explicit primary yaml', function () {
    $fromVariant = HeaderActions::fromSchema([
        'actions' => [
            'go' => [
                'label' => 'Go',
                'action' => 'save',
                'variant' => 'primary',
            ],
        ],
    ]);
    expect($fromVariant[0]['primary'] ?? false)->toBeTrue();

    $fromBool = HeaderActions::fromSchema([
        'actions' => [
            'go' => [
                'label' => 'Go',
                'action' => 'save',
                'variant' => 'outline',
                'primary' => true,
            ],
        ],
    ]);
    expect($fromBool[0]['primary'] ?? false)->toBeTrue();
});

test('fromSchema maps primary alias to default', function () {
    $actions = HeaderActions::fromSchema([
        'actions' => [
            'create' => [
                'label' => 'Create',
                'href' => '/posts/create',
                'variant' => 'primary',
            ],
        ],
    ]);

    expect($actions[0]['variant'])->toBe('default');
});

test('fromSchema uses outline for omitted variant and default for primary CTA', function () {
    $actions = HeaderActions::fromSchema([
        'actions' => [
            'category' => [
                'label' => 'New Category',
                'href' => '/categories/create',
            ],
            'create' => [
                'label' => 'Create',
                'action' => 'create',
                'variant' => 'primary',
            ],
        ],
    ]);

    expect($actions[0]['variant'])->toBe('outline');
    expect($actions[1]['variant'])->toBe('default');
});

test('fromSchema keeps action-based header actions', function () {
    $actions = HeaderActions::fromSchema([
        'actions' => [
            'create' => [
                'label' => 'Create',
                'action' => 'create',
                'icon' => 'plus',
            ],
        ],
    ]);

    expect($actions)->toHaveCount(1);
    expect($actions[0])->toMatchArray([
        'id' => 'create',
        'label' => 'Create',
        'action' => 'create',
        'icon' => 'plus',
        'variant' => 'outline',
    ]);
});

test('fromSchema includes success_redirect for action-based header actions', function () {
    $actions = HeaderActions::fromSchema([
        'actions' => [
            'save' => [
                'label' => 'Save',
                'action' => 'save',
                'success_redirect' => 'list',
            ],
            'bad' => [
                'label' => 'Bad',
                'action' => 'save',
                'success_redirect' => 'not-a-valid-target',
            ],
        ],
    ]);

    expect($actions)->toHaveCount(2);
    expect($actions[0]['success_redirect'])->toBe('list');
    expect($actions[1])->not->toHaveKey('success_redirect');
});

test('fromSchema includes success_message and confirm for action-based header actions', function () {
    $actions = HeaderActions::fromSchema([
        'actions' => [
            'save' => [
                'label' => 'Save',
                'action' => 'save',
                'success_message' => 'Saved OK',
                'confirm' => true,
            ],
            'delete' => [
                'label' => 'Delete',
                'action' => 'delete',
                'confirm' => false,
            ],
            'restore' => [
                'label' => 'Restore',
                'action' => 'restore',
                'confirm' => 'yes',
            ],
        ],
    ]);

    expect($actions)->toHaveCount(3);
    expect($actions[0])->toMatchArray([
        'id' => 'save',
        'success_message' => 'Saved OK',
        'confirm' => true,
    ]);
    expect($actions[1])->not->toHaveKey('confirm');
    expect($actions[2])->not->toHaveKey('confirm');
});

test('fromSchema ignores invalid header actions without exactly one target', function () {
    $actions = HeaderActions::fromSchema([
        'actions' => [
            'missing-target' => [
                'label' => 'Missing',
            ],
            'both-targets' => [
                'label' => 'Both',
                'action' => 'create',
                'href' => '/posts/create',
            ],
            'legacy-url' => [
                'label' => 'Legacy',
                'url' => '/posts/create',
            ],
        ],
    ]);

    expect($actions)->toBe([]);
});

test('fromSchema sets disable_until_dirty when yaml requests it', function () {
    config()->set('flatpack.forms.disable_actions_until_dirty', false);

    $actions = HeaderActions::fromSchema([
        'actions' => [
            'save' => [
                'label' => 'Save',
                'action' => 'save',
                'disable_until_dirty' => true,
            ],
        ],
    ]);

    expect($actions[0]['disable_until_dirty'] ?? false)->toBeTrue();
});

test('fromSchema sets disable_until_dirty for all actions when global config is true', function () {
    config()->set('flatpack.forms.disable_actions_until_dirty', true);

    $actions = HeaderActions::fromSchema([
        'actions' => [
            'save' => [
                'label' => 'Save',
                'action' => 'save',
            ],
            'back' => [
                'label' => 'Back',
                'href' => '/list',
            ],
        ],
    ]);

    expect($actions[0]['disable_until_dirty'] ?? false)->toBeTrue();
    expect($actions[1]['disable_until_dirty'] ?? false)->toBeTrue();
});
