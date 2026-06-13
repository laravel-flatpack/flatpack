<?php

declare(strict_types=1);

use Flatpack\Console\Support\DiscoverFlatpackIconNamesService;
use Flatpack\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;

uses(TestCase::class);

it('discovers icon names from the lucide menu registry', function (): void {
    $icons = (new DiscoverFlatpackIconNamesService(new Filesystem))->discover();

    expect($icons)->toBeArray()->not->toBeEmpty()
        ->and($icons)->toContain('folder-open');
});
