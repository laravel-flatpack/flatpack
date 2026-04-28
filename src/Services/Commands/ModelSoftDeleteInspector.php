<?php

declare(strict_types=1);

namespace Flatpack\Services\Commands;

use Illuminate\Database\Eloquent\SoftDeletes;

final class ModelSoftDeleteInspector
{
    /**
     * @param  class-string  $modelClass
     */
    public function usesSoftDeletes(string $modelClass): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);
    }
}
