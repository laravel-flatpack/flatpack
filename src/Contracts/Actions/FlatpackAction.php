<?php

declare(strict_types=1);

namespace Flatpack\Contracts\Actions;

use Flatpack\Actions\FlatpackActionContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

interface FlatpackAction
{
    /**
     * Whether the user may run this action for the model class and optional instance.
     *
     * Flatpack validates $modelClass before calling this method.
     */
    public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool;

    public function handle(FlatpackActionContext $context): mixed;
}
