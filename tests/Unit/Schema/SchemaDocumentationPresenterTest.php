<?php

declare(strict_types=1);

use Flatpack\Schema\SchemaDocumentationPresenter;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

it('builds structured docs for the form schema', function (): void {
    $doc = (new SchemaDocumentationPresenter)->buildDocument('form');

    expect($doc)->toHaveKeys(['id', 'title', 'definitions', 'root', 'raw'])
        ->and($doc['id'])->toBe('form')
        ->and($doc['definitions'])->not->toBeEmpty();
});

it('builds structured docs for the list schema', function (): void {
    $doc = (new SchemaDocumentationPresenter)->buildDocument('list');

    expect($doc['id'])->toBe('list')
        ->and($doc['root'])->toBeArray();
});
