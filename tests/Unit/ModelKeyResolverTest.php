<?php

declare(strict_types=1);

use Flatpack\Support\ModelKeyResolver;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\PostBySlug;

test('resolve returns id fallback for missing or invalid model classes', function () {
    $resolver = new ModelKeyResolver();

    expect($resolver->resolve(null))->toBe('id');
    expect($resolver->resolve(''))->toBe('id');
    expect($resolver->resolve(stdClass::class))->toBe('id');
    expect($resolver->resolve('App\\Models\\DoesNotExist'))->toBe('id');
});

test('resolve returns primary key for eloquent model classes', function () {
    $resolver = new ModelKeyResolver();

    expect($resolver->resolve(Post::class))->toBe('id');
    expect($resolver->resolve(PostBySlug::class))->toBe('slug');
});
