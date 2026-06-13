<?php

declare(strict_types=1);

use Flatpack\Services\Widgets\TableWidgetDataResolver;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Flatpack\Widgets\Data\TableWidgetData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

uses(TestCase::class);

beforeEach(function () {
    $this->resolver = app(TableWidgetDataResolver::class);
});

test('rejects bare eloquent builder', function () {
    expect(fn () => $this->resolver->resolve(User::query()))
        ->toThrow(InvalidArgumentException::class, 'Execute the query first');
});

test('resolves a list array of associative rows', function () {
    $table = $this->resolver->resolve([
        ['id' => 1, 'name' => 'Ada'],
        ['id' => 2, 'name' => 'Bob'],
    ]);

    expect($table)->toBeInstanceOf(TableWidgetData::class)
        ->and($table->rows)->toBe([
            ['id' => 1, 'name' => 'Ada'],
            ['id' => 2, 'name' => 'Bob'],
        ])
        ->and($table->columns)->toBeNull();
});

test('resolves a single associative row array', function () {
    $table = $this->resolver->resolve(['id' => 1, 'name' => 'Ada']);

    expect($table->rows)->toBe([['id' => 1, 'name' => 'Ada']]);
});

test('resolves a Collection of Model instances and projects via column ids', function () {
    User::factory()->createOne(['name' => 'Ada']);
    User::factory()->createOne(['name' => 'Bob']);

    $table = $this->resolver->resolve(
        User::query()->orderBy('id')->get(),
        columnIds: ['id', 'name'],
    );

    expect($table->rows)->toHaveCount(2)
        ->and(array_keys($table->rows[0]))->toBe(['id', 'name'])
        ->and($table->rows[0]['name'])->toBe('Ada')
        ->and($table->rows[1]['name'])->toBe('Bob');
});

test('resolves a Collection of Model instances using full toArray when no column ids', function () {
    User::factory()->createOne(['name' => 'Ada']);

    $table = $this->resolver->resolve(User::query()->get());

    expect($table->rows)->toHaveCount(1)
        ->and($table->rows[0])->toHaveKey('email');
});

test('resolves a Collection of plain associative arrays', function () {
    $table = $this->resolver->resolve(Collection::make([
        ['id' => 1, 'label' => 'one'],
        ['id' => 2, 'label' => 'two'],
    ]));

    expect($table->rows)->toBe([
        ['id' => 1, 'label' => 'one'],
        ['id' => 2, 'label' => 'two'],
    ]);
});

test('resolves a Model directly', function () {
    $user = User::factory()->createOne(['name' => 'Ada']);

    $table = $this->resolver->resolve($user, columnIds: ['id', 'name']);

    expect($table->rows)->toHaveCount(1)
        ->and($table->rows[0])->toBe(['id' => $user->id, 'name' => 'Ada']);
});

test('resolves a single JsonResource into one row', function () {
    $user = User::factory()->createOne();
    $resource = new WidgetTableTestUserResource($user);

    $table = $this->resolver->resolve($resource, request: Request::create('/'));

    expect($table->rows)->toHaveCount(1)
        ->and($table->rows[0]['email'])->toBe($user->email);
});

test('resolves a ResourceCollection into multiple rows', function () {
    User::factory()->count(3)->create();
    $collection = WidgetTableTestUserResource::collection(User::query()->orderBy('id')->get());

    $table = $this->resolver->resolve($collection, request: Request::create('/'));

    expect($table->rows)->toHaveCount(3);
});

test('snapshot toArray contains rows only when no columns supplied', function () {
    $table = $this->resolver->resolve([['id' => 1]]);

    expect($table->toArray())->toBe(['rows' => [['id' => 1]]]);
});

test('snapshot toArray contains columns when constructed with them', function () {
    $table = new TableWidgetData(
        rows: [['id' => 1, 'name' => 'Ada']],
        columns: ['name' => ['label' => 'Name']],
    );

    expect($table->toArray())->toBe([
        'rows' => [['id' => 1, 'name' => 'Ada']],
        'columns' => ['name' => ['label' => 'Name']],
    ]);
});

final class WidgetTableTestUserResource extends JsonResource
{
    /**
     * @return array{id: int, name: string, email: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
