<?php

declare(strict_types=1);

use Flatpack\Lists\ListHeaderActions;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

test('fromSchema builds list actions with prefixed href', function () {
    config()->set('flatpack.prefix', 'flatpack');

    $actions = ListHeaderActions::fromSchema([
        'actions' => [
            'create' => [
                'label' => 'Create',
                'url' => '/posts/create',
            ],
        ],
    ]);

    expect($actions)->toHaveCount(1);
    expect($actions[0])->toMatchArray([
        'id' => 'create',
        'label' => 'Create',
        'href' => '/flatpack/posts/create',
        'variant' => 'outline',
    ]);
});

test('fromSchema passes through allowed variant', function () {
    config()->set('flatpack.prefix', 'flatpack');

    $actions = ListHeaderActions::fromSchema([
        'actions' => [
            'create' => [
                'label' => 'Create',
                'url' => '/posts/create',
                'variant' => 'outline',
            ],
        ],
    ]);

    expect($actions[0]['variant'])->toBe('outline');
});

test('fromSchema defaults invalid variant to outline', function () {
    config()->set('flatpack.prefix', 'flatpack');

    $actions = ListHeaderActions::fromSchema([
        'actions' => [
            'create' => [
                'label' => 'Create',
                'url' => '/posts/create',
                'variant' => 'not-a-real-variant',
            ],
        ],
    ]);

    expect($actions[0]['variant'])->toBe('outline');
});

test('fromSchema maps primary alias to default', function () {
    config()->set('flatpack.prefix', 'flatpack');

    $actions = ListHeaderActions::fromSchema([
        'actions' => [
            'create' => [
                'label' => 'Create',
                'url' => '/posts/create',
                'variant' => 'primary',
            ],
        ],
    ]);

    expect($actions[0]['variant'])->toBe('default');
});

test('fromSchema uses outline for omitted variant and default for primary CTA', function () {
    config()->set('flatpack.prefix', 'flatpack');

    $actions = ListHeaderActions::fromSchema([
        'actions' => [
            'category' => [
                'label' => 'New Category',
                'url' => '/categories/create',
            ],
            'create' => [
                'label' => 'Create',
                'url' => '/posts/create',
                'variant' => 'primary',
            ],
        ],
    ]);

    expect($actions[0]['variant'])->toBe('outline');
    expect($actions[1]['variant'])->toBe('default');
});
