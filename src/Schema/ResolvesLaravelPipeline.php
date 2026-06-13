<?php

declare(strict_types=1);

namespace Flatpack\Schema;

use Illuminate\Pipeline\Pipeline;

/**
 * Shared testing seam for schema normalizers that run Laravel {@see Pipeline} stages.
 */
trait ResolvesLaravelPipeline
{
    private function resolvePipeline(): Pipeline
    {
        return $this->pipeline ?? new Pipeline(app());
    }
}
