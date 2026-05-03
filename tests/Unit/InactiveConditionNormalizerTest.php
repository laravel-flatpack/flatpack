<?php

declare(strict_types=1);

use Flatpack\Schema\InactiveConditionNormalizer;

test('normalizeInactiveUntil returns null for non-array input', function () {
    expect(InactiveConditionNormalizer::normalizeInactiveUntil(null))->toBeNull()
        ->and(InactiveConditionNormalizer::normalizeInactiveUntil('x'))->toBeNull();
});

test('normalizeInactiveUntil builds all any and message from valid shapes', function () {
    $out = InactiveConditionNormalizer::normalizeInactiveUntil([
        'all' => [
            ['form.dirty' => true],
        ],
        'any' => [
            ['list.selection.min' => 1],
        ],
        'message' => '  Needs context  ',
    ]);

    expect($out)->toMatchArray([
        'all' => [['form.dirty' => true]],
        'any' => [['list.selection.min' => 1]],
        'message' => 'Needs context',
    ]);
});

test('normalizeInactiveUntil returns null when all and any predicate lists are empty', function () {
    expect(InactiveConditionNormalizer::normalizeInactiveUntil([
        'all' => [],
        'any' => [],
    ]))->toBeNull();
});

test('normalizeInactivePredicates collects supported single-key predicates', function () {
    $out = InactiveConditionNormalizer::normalizeInactivePredicates([
        ['form.dirty' => false],
        ['form.mode_in' => ['create', 'invalid', 'edit']],
        ['list.selection.min' => 2],
        ['list.search_present' => true],
        ['list.filters_applied' => false],
        ['ignored' => true],
        [['too' => true, 'many' => true]],
    ]);

    expect($out)->toBe([
        ['form.dirty' => false],
        ['form.mode_in' => ['create', 'edit']],
        ['list.selection.min' => 2],
        ['list.search_present' => true],
        ['list.filters_applied' => false],
    ]);
});

test('normalizeInactivePredicates collects form field predicates', function () {
    $out = InactiveConditionNormalizer::normalizeInactivePredicates([
        ['form.field_eq' => ['field' => 'status', 'value' => 'draft']],
        ['form.field_eq' => ['field' => ' ', 'value' => 'x']],
        ['form.field_in' => ['field' => 'tags', 'values' => ['a', 'b', null, 1]]],
        ['form.field_in' => ['field' => 'x', 'values' => []]],
        ['form.field_truthy' => ['field' => 'published']],
        ['form.field_present' => ['field' => 'note']],
        ['form.field_null' => ['field' => 'archived_at']],
    ]);

    expect($out)->toBe([
        ['form.field_eq' => ['field' => 'status', 'value' => 'draft']],
        ['form.field_in' => ['field' => 'tags', 'values' => ['a', 'b', null, 1]]],
        ['form.field_truthy' => ['field' => 'published']],
        ['form.field_present' => ['field' => 'note']],
        ['form.field_null' => ['field' => 'archived_at']],
    ]);
});
