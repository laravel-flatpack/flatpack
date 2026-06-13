<?php

declare(strict_types=1);

use Flatpack\Http\Resources\FlatpackSchema;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

test('FlatpackSchema omits schema when empty and includes records when present', function () {
    $request = request();

    // resolve() applies JsonResource filtering (MissingValue); toArray() alone does not.
    $withRecordsOnly = (new FlatpackSchema(['records' => [['id' => 1]]]))->resolve($request);
    expect($withRecordsOnly)->toHaveKey('records')->not->toHaveKey('schema');

    $withSchema = (new FlatpackSchema([
        'schema' => ['fields' => []],
    ]))->resolve($request);
    expect($withSchema)->toHaveKey('schema');

    $withPaginationKey = (new FlatpackSchema([
        'pagination' => ['current_page' => 1],
    ]))->resolve($request);
    expect($withPaginationKey)->toHaveKey('pagination');
});
