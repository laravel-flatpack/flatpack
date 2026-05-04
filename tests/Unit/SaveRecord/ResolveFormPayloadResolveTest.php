<?php

declare(strict_types=1);

use Flatpack\Services\SaveRecord\ResolveFormPayload;
use Flatpack\Tests\TestCase;
use Illuminate\Http\Request;

uses(TestCase::class);

it('returns values array when present on the request', function (): void {
    $resolver = new ResolveFormPayload;
    $request = Request::create('/', 'POST', ['values' => ['title' => 'Hello']]);

    expect($resolver->resolve($request))->toBe(['title' => 'Hello']);
});

it('maps inline field/value pairs when values array is absent', function (): void {
    $resolver = new ResolveFormPayload;
    $request = Request::create('/', 'POST', [
        'field' => 'slug',
        'value' => 'my-post',
    ]);

    expect($resolver->resolve($request))->toBe(['slug' => 'my-post']);
});

it('returns null when neither values nor field are usable', function (): void {
    $resolver = new ResolveFormPayload;
    $request = Request::create('/', 'POST', []);

    expect($resolver->resolve($request))->toBeNull();
});
