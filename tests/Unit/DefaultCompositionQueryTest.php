<?php

declare(strict_types=1);

use Flatpack\Composition\DefaultCompositionQuery;
use Flatpack\Contracts\Composition\CompositionLoader;
use Flatpack\Contracts\Composition\CompositionNotFoundException;

it('memoizes loaded composition by entity and type within request scope', function () {
    $state = new class
    {
        public int $calls = 0;
    };

    $loader = new class($state) implements CompositionLoader
    {
        public function __construct(private object $state) {}

        public function load(string $entity, string $type): array
        {
            $this->state->calls++;

            return ['name' => $entity . '-' . $type];
        }
    };

    $query = new DefaultCompositionQuery($loader);

    $first = $query->optional('posts', 'list');
    $second = $query->optional('posts', 'list');

    expect($first)->toBe($second);
    expect($state->calls)->toBe(1);
});

it('memoizes missing compositions to avoid repeated loader exceptions', function () {
    $state = new class
    {
        public int $calls = 0;
    };

    $loader = new class($state) implements CompositionLoader
    {
        public function __construct(private object $state) {}

        public function load(string $entity, string $type): array
        {
            $this->state->calls++;
            throw CompositionNotFoundException::forEntity($entity, $type);
        }
    };

    $query = new DefaultCompositionQuery($loader);

    expect($query->optional('posts', 'missing'))->toBeNull();
    expect($query->optional('posts', 'missing'))->toBeNull();
    expect($state->calls)->toBe(1);
});
