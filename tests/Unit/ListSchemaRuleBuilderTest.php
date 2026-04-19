<?php

declare(strict_types=1);

use Flatpack\Schema\Lists\Validation\ListSchemaRuleBuilder;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rules\In;

uses(TestCase::class, RefreshDatabase::class);

test('it builds rules only for editable list columns', function () {
    $builder = new ListSchemaRuleBuilder;
    $rules = $builder->rulesForValues([
        'columns' => [
            'title' => [
                'id' => 'title',
                'type' => 'text',
                'label' => 'Title',
                'editable' => true,
            ],
            'status' => [
                'id' => 'status',
                'type' => 'text',
                'label' => 'S',
                'editable' => false,
            ],
        ],
    ], Post::class);

    expect($rules)->toHaveKey('values.title')
        ->and($rules)->not->toHaveKey('values.status');
});

test('it builds select rules from column options', function () {
    $builder = new ListSchemaRuleBuilder;
    $rules = $builder->rulesForValues([
        'columns' => [
            [
                'id' => 'status',
                'type' => 'select',
                'label' => 'Status',
                'editable' => true,
                'options' => [
                    'draft' => 'Draft',
                    'live' => 'Live',
                ],
            ],
        ],
    ], Post::class);

    expect($rules)->toHaveKey('values.status')
        ->and($rules['values.status'][0])->toBe('nullable')
        ->and($rules['values.status'][1])->toBeInstanceOf(In::class);
});

test('it merges passthrough YAML rules strings', function () {
    $builder = new ListSchemaRuleBuilder;
    $rules = $builder->rulesForValues([
        'columns' => [
            [
                'id' => 'slug',
                'type' => 'text',
                'label' => 'Slug',
                'editable' => true,
                'rules' => 'min:3',
            ],
        ],
    ], Post::class);

    expect($rules['values.slug'])->toContain('nullable')
        ->and($rules['values.slug'])->toContain('string')
        ->and($rules['values.slug'])->toContain('min:3');
});

test('required column uses required validation', function () {
    $builder = new ListSchemaRuleBuilder;
    $rules = $builder->rulesForValues([
        'columns' => [
            [
                'id' => 'title',
                'type' => 'text',
                'label' => 'Title',
                'editable' => true,
                'required' => true,
            ],
        ],
    ], Post::class);

    expect($rules['values.title'][0])->toBe('required')
        ->and($rules['values.title'])->toContain('string');
});

test('editable relation column adds exists rule when configured', function () {
    $builder = new ListSchemaRuleBuilder;
    $rules = $builder->rulesForValues([
        'columns' => [
            [
                'id' => 'category_id',
                'type' => 'relation',
                'label' => 'Category',
                'editable' => true,
                'relation' => 'category',
                'relation_name' => 'name',
                'relation_value' => 'id',
            ],
        ],
    ], Post::class);

    expect($rules)->toHaveKey('values.category_id')
        ->and($rules['values.category_id'][0])->toBe('nullable')
        ->and($rules['values.category_id'][1])->toStartWith('exists:');
});
