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

test('reorder action throws on unknown sort column', function () {
    Post::factory()->create(['title' => 'First', 'sort_order' => 1]);

    expect(fn () => app(ReorderActionHandler::class)->handle(reorderContext(
        record: '1',
        position: 1,
        reorderableColumn: 'missing_column',
    )))->toThrow(InvalidArgumentException::class);
});

function reorderContext(
    string $record,
    int $position,
    string $reorderableColumn = 'sort_order',
): FlatpackActionContext {
    $request = Request::create(
        uri: '/flatpack/posts/' . $record . '/reorder',
        method: 'PATCH',
        parameters: ['position' => $position],
    );

    return new FlatpackActionContext(
        request: $request,
        entity: 'posts',
        actionName: 'reorder',
        modelClass: Post::class,
        record: $record,
        compositionType: 'list',
        schema: [
            'model' => Post::class,
            'reorderable' => true,
            'reorderableColumn' => $reorderableColumn,
        ],
        model: null,
    );
}
