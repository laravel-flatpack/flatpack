<?php

declare(strict_types=1);

use Flatpack\Facades\Flatpack;
use Flatpack\Tests\TestCase;

uses(TestCase::class);

test('flatpack reads list pagination and route prefix from config', function () {
    config()->set('flatpack.prefix', 'admin');
    config()->set('flatpack.list.per_page', 25);
    config()->set('flatpack.list.max_per_page', 50);

    expect(Flatpack::routePrefix())->toBe('admin');
    expect(Flatpack::defaultListPerPage())->toBe(25);
    expect(Flatpack::maxListPerPage())->toBe(50);
});

test('flatpack reads dashboard entity and auth guard from config', function () {
    config()->set('flatpack.dashboard_entity', 'home');
    config()->set('flatpack.guard', 'api');

    expect(Flatpack::dashboardEntity())->toBe('home');
    expect(Flatpack::authGuard())->toBe('api');
});

test('flatpack version is a non-empty string', function () {
    expect(Flatpack::version())->toBeString()->not->toBe('');
});

test('composer package version helper returns a string', function () {
    expect(Flatpack::composerPackageVersion())->toBeString();
});
