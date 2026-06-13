<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final readonly class BulkSelectionQuery
{
    public function __construct(
        public Model $model,
        public Builder $query,
    ) {}
}
