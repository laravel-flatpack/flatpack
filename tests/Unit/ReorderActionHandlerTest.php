<?php

declare(strict_types=1);

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Actions\Handlers\ReorderActionHandler;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Schema::table('posts', static function ($table): void {
        $table->unsignedBigInteger('sort_order')->default(0)->index();
    });
});

test('reorder action moves row up and shifts siblings down', function () {
    $first = Post::factory()->create(['title' => 'First', 'sort_order' => 1]);
    $second = Post::factory()->create(['title' => 'Second', 'sort_order' => 2]);
    $third = Post::factory()->create(['title' => 'Third', 'sort_order' => 3]);

    $record = app(ReorderActionHandler::class)->handle(reorderContext(
        record: (string) $third->getKey(),
        position: 1,
    ));

    expect((int) $record->sort_order)->toBe(1);
    expect((int) (Post::query()->find($first->getKey())?->sort_order ?? 0))->toBe(2);
    expect((int) (Post::query()->find($second->getKey())?->sort_order ?? 0))->toBe(3);
});

test('reorder action moves row down and shifts siblings up', function () {
    $first = Post::factory()->create(['title' => 'First', 'sort_order' => 1]);
    $second = Post::factory()->create(['title' => 'Second', 'sort_order' => 2]);
    $third = Post::factory()->create(['title' => 'Third', 'sort_order' => 3]);

    $record = app(ReorderActionHandler::class)->handle(reorderContext(
        record: (string) $first->getKey(),
        position: 3,
    ));

    expect((int) $record->sort_order)->toBe(3);
    expect((int) (Post::query()->find($second->getKey())?->sort_order ?? 0))->toBe(1);
    expect((int) (Post::query()->find($third->getKey())?->sort_order ?? 0))->toBe(2);
});

test('reorder action no-ops when target position matches current position', function () {
    $first = Post::factory()->create(['title' => 'First', 'sort_order' => 1]);
    $second = Post::factory()->create(['title' => 'Second', 'sort_order' => 2]);

    $record = app(ReorderActionHandler::class)->handle(reorderContext(
        record: (string) $second->getKey(),
        position: 2,
    ));

    expect((int) $record->sort_order)->toBe(2);
    expect((int) (Post::query()->find($first->getKey())?->sort_order ?? 0))->toBe(1);
});

test('reorder action clamps target position to row count bounds', function () {
    $first = Post::factory()->create(['title' => 'First', 'sort_order' => 1]);
    $second = Post::factory()->create(['title' => 'Second', 'sort_order' => 2]);
    $third = Post::factory()->create(['title' => 'Third', 'sort_order' => 3]);

    $record = app(ReorderActionHandler::class)->handle(reorderContext(
        record: (string) $first->getKey(),
        position: 999,
    ));

    expect((int) $record->sort_order)->toBe(3);
    expect((int) (Post::query()->find($second->getKey())?->sort_order ?? 0))->toBe(1);
    expect((int) (Post::query()->find($third->getKey())?->sort_order ?? 0))->toBe(2);
});

test('reorder action normalizes sparse sort values before moving rows', function () {
    $first = Post::factory()->create(['title' => 'First', 'sort_order' => 10]);
    $second = Post::factory()->create(['title' => 'Second', 'sort_order' => 20]);
    $third = Post::factory()->create(['title' => 'Third', 'sort_order' => 50]);

    $record = app(ReorderActionHandler::class)->handle(reorderContext(
        record: (string) $second->getKey(),
        position: 1,
    ));

    expect((int) $record->sort_order)->toBe(1);
    expect((int) (Post::query()->find($first->getKey())?->sort_order ?? 0))->toBe(2);
    expect((int) (Post::query()->find($third->getKey())?->sort_order ?? 0))->toBe(3);
});

test('reorder action throws on unknown sort column', function () {
    Post::factory()->create(['title' => 'First', 'sort_order' => 1]);

    expect(fn () => app(ReorderActionHandler::class)->handle(reorderContext(
        record: '1',
        position: 1,
        reorderableColumn: 'missing_column',
    )))->toThrow(InvalidArgumentException::class);
});

test('reorder action throws when model class is invalid', function () {
    expect(fn () => app(ReorderActionHandler::class)->handle(reorderContext(
        record: '1',
        position: 1,
        modelClass: '',
    )))->toThrow(InvalidArgumentException::class, 'Cannot reorder records: model class is invalid.');
});

test('reorder action throws when record id is missing', function () {
    expect(fn () => app(ReorderActionHandler::class)->handle(reorderContext(
        record: '',
        position: 1,
    )))->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('reorder action throws when reorder column is not configured', function () {
    expect(fn () => app(ReorderActionHandler::class)->handle(reorderContext(
        record: '1',
        position: 1,
        schema: [
            'model' => Post::class,
            'reorderable' => false,
        ],
    )))->toThrow(InvalidArgumentException::class, 'Cannot reorder records: reorderable column is not configured.');
});

test('reorder action ignores unknown list scope method', function () {
    $first = Post::factory()->create(['title' => 'First', 'sort_order' => 1, 'status' => 'draft']);
    $second = Post::factory()->create(['title' => 'Second', 'sort_order' => 2, 'status' => 'draft']);

    $record = app(ReorderActionHandler::class)->handle(reorderContext(
        record: (string) $second->getKey(),
        position: 1,
        scope: 'totallyMissingScope',
    ));

    expect((int) $record->sort_order)->toBe(1);
});

test('reorder action applies configured eloquent scope', function () {
    $draftA = Post::factory()->create(['title' => 'D1', 'sort_order' => 1, 'status' => 'draft']);
    $draftB = Post::factory()->create(['title' => 'D2', 'sort_order' => 2, 'status' => 'draft']);
    Post::factory()->create(['title' => 'Published', 'sort_order' => 3, 'status' => 'active']);

    $record = app(ReorderActionHandler::class)->handle(reorderContext(
        record: (string) $draftB->getKey(),
        position: 1,
        scope: 'draftOnly',
    ));

    expect((int) $record->sort_order)->toBe(1);
    expect((int) (Post::query()->find($draftA->getKey())?->sort_order ?? 0))->toBe(2);
});

function reorderContext(
    string $record,
    int $position,
    string $reorderableColumn = 'sort_order',
    string $modelClass = Post::class,
    ?string $scope = null,
    ?array $schema = null,
): FlatpackActionContext {
    $request = Request::create(
        uri: '/flatpack/posts/' . ($record !== '' ? $record : '0') . '/reorder',
        method: 'PATCH',
        parameters: ['position' => $position],
    );

    return new FlatpackActionContext(
        request: $request,
        entity: 'posts',
        actionName: 'reorder',
        modelClass: $modelClass,
        record: $record,
        compositionType: 'list',
        scope: $scope,
        schema: $schema ?? [
            'model' => Post::class,
            'reorderable' => true,
            'reorderableColumn' => $reorderableColumn,
        ],
        model: null,
    );
}
