<?php

declare(strict_types=1);

use Flatpack\Schema\CompositionSchemaKeysGenerator;
use Flatpack\Tests\TestCase;
use Illuminate\Support\Facades\File;

uses(TestCase::class);

it('keeps generated TypeScript composition schema keys aligned with schema files', function (): void {
    $root = dirname(__DIR__, 2);
    $generator = new CompositionSchemaKeysGenerator;
    $form = json_decode(File::get($root . '/resources/schema/form.json'), true, flags: JSON_THROW_ON_ERROR);
    $list = json_decode(File::get($root . '/resources/schema/list.json'), true, flags: JSON_THROW_ON_ERROR);
    assert(is_array($form));
    assert(is_array($list));

    $sets = $generator->extractKeySets($form, $list);
    $actual = File::get($root . '/resources/js/lib/generated/composition-schema-keys.ts');

    expect($generator->typeScriptModuleMatchesKeySets($actual, $sets))->toBeTrue();
});
