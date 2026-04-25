<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms;

use Flatpack\Support\CompositionDebugLog;

final readonly class FormSchemaNormalizationResult
{
    /**
     * @param  array<string, mixed>|null  $schema
     */
    public function __construct(
        public ?array $schema,
        public ?CompositionDebugLog $debugLog,
    ) {}
}
