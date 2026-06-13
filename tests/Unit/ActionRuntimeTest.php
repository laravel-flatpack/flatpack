<?php

declare(strict_types=1);

use Flatpack\Services\Runtime\ActionRuntime;
use Flatpack\Support\Exceptions\ActionRuntimeException;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

test('resolveRecordActionHandler throws runtime exception when action is missing', function () {
    config()->set('flatpack.actions', [
        'save' => Flatpack\Actions\Handlers\SaveRecordHandler::class,
    ]);

    expect(fn () => app(ActionRuntime::class)->resolveRecordActionHandler('create'))
        ->toThrow(ActionRuntimeException::class, 'Flatpack action "create" is not configured.');
});

test('resolveBulkActionHandler throws runtime exception when action is missing', function () {
    config()->set('flatpack.bulk_actions', [
        'delete' => Flatpack\Actions\Handlers\BulkDeleteHandler::class,
    ]);

    expect(fn () => app(ActionRuntime::class)->resolveBulkActionHandler('restore'))
        ->toThrow(ActionRuntimeException::class, 'Flatpack bulk action "restore" is not configured.');
});

test('resolveRecordModel throws runtime exception when model class is invalid', function () {
    expect(fn () => app(ActionRuntime::class)->resolveRecordModel('Invalid\\MissingModel', '1', 'form'))
        ->toThrow(ActionRuntimeException::class, 'Flatpack form model is not configured.');
});

test('resolveRecordModel resolves configured model record', function () {
    $post = Post::factory()->createOne();

    $model = app(ActionRuntime::class)->resolveRecordModel(
        Post::class,
        (string) $post->getKey(),
        'form',
    );

    expect($model->getKey())->toBe($post->getKey());
});
