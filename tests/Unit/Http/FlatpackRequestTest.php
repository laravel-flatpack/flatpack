<?php

declare(strict_types=1);

use Flatpack\Http\FlatpackRequest;
use Flatpack\Tests\TestCase;
use Illuminate\Http\Request;

uses(TestCase::class);

it('matches requests under the configured flatpack path prefix', function (): void {
    config(['flatpack.http.prefix' => 'admin']);

    $r = Request::create('/admin/posts', 'GET');
    expect(FlatpackRequest::matches($r))->toBeTrue();
});

it('matches when the first path segment equals the prefix', function (): void {
    config(['flatpack.http.prefix' => 'fp']);

    $r = Request::create('/fp', 'GET');
    expect(FlatpackRequest::matches($r))->toBeTrue();
});

it('does not match unrelated paths', function (): void {
    config(['flatpack.http.prefix' => 'flatpack']);

    $r = Request::create('/app/other', 'GET');
    expect(FlatpackRequest::matches($r))->toBeFalse();
});

it('detects flatpack login route', function (): void {
    $r = Request::create('/flatpack/login', 'GET');
    $r->setRouteResolver(function () {
        $route = new Illuminate\Routing\Route('GET', 'flatpack/login', []);
        $route->name('flatpack.login');

        return $route;
    });

    expect(FlatpackRequest::isFlatpackLoginRoute($r))->toBeTrue();
});
