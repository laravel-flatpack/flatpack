<?php

declare(strict_types=1);

namespace Flatpack\Http;

use Flatpack\Support\CompositionDebugLog;

final readonly class FlatpackResponseOptions
{
    public function __construct(
        public ?CompositionDebugLog $compositionDebugLog = null,
        public bool $skipFormSchemaNormalize = false,
    ) {}
}
