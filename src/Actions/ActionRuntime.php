<?php

declare(strict_types=1);

namespace Flatpack\Actions;

use Flatpack\Services\Actions\ActionRuntime as ServicesActionRuntime;

/** @deprecated Use Flatpack\Services\Actions\ActionRuntime instead. */
final class ActionRuntime
{
    public function __construct(private readonly ServicesActionRuntime $runtime) {}

    public function __call(string $name, array $arguments): mixed
    {
        return $this->runtime->{$name}(...$arguments);
    }
}
