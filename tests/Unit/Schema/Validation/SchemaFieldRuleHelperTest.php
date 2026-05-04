<?php

declare(strict_types=1);

use Flatpack\Schema\Validation\SchemaFieldRuleHelper;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Validation\Rules\In;

uses(TestCase::class);

it('selectInRule returns null when options are missing or not an array', function (): void {
    expect(SchemaFieldRuleHelper::selectInRule([]))->toBeNull()
        ->and(SchemaFieldRuleHelper::selectInRule(['options' => 'x']))->toBeNull();
});

it('selectInRule returns null when no option values are extractable', function (): void {
    expect(SchemaFieldRuleHelper::selectInRule([
        'options' => [
            ['label' => 'A'],
            'bad',
        ],
    ]))->toBeNull();
});

it('selectInRule builds In rule from list options', function (): void {
    $rule = SchemaFieldRuleHelper::selectInRule([
        'options' => [
            ['value' => 'a', 'label' => 'A'],
            ['value' => 'b', 'label' => 'B'],
        ],
    ]);

    expect($rule)->toBeInstanceOf(In::class);
});

it('selectInRule builds In rule from map options', function (): void {
    $rule = SchemaFieldRuleHelper::selectInRule([
        'options' => [
            'x' => 'X',
            'y' => 'Y',
        ],
    ]);

    expect($rule)->toBeInstanceOf(In::class);
});

it('relationExistsRule returns null when relation metadata is incomplete', function (): void {
    expect(SchemaFieldRuleHelper::relationExistsRule(Post::class, [
        'relation' => 'category',
    ]))->toBeNull();
});

it('relationExistsRule returns exists string for a valid BelongsTo relation', function (): void {
    $rule = SchemaFieldRuleHelper::relationExistsRule(Post::class, [
        'relation' => 'category',
        'relation_name' => 'name',
        'relation_value' => 'id',
    ]);

    expect($rule)->toBeString()->toStartWith('exists:');
    expect($rule)->toContain(',id');
});

it('relationExistsRule reads camelCase relation keys', function (): void {
    $rule = SchemaFieldRuleHelper::relationExistsRule(Post::class, [
        'relation' => 'category',
        'relationName' => 'name',
        'relationValue' => 'id',
    ]);

    expect($rule)->not->toBeNull();
});
