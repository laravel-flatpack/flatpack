<?php

declare(strict_types=1);

use Flatpack\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

uses(TestCase::class);

test('flatpack generate composition schema keys check exits successfully when files are in sync', function () {
    expect(Artisan::call('flatpack:generate-composition-schema-keys', ['--check' => true]))->toBe(0);
});
