<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Shared policy checks for Flatpack controllers that read/write model-backed resources.
 */
trait AuthorizesModelAbility
{
    protected function ensureModelAbility(
        Request $request,
        string $modelClass,
        string $ability,
        ?Model $model = null,
    ): void {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }
        if ($modelClass === '' || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            abort(403);
        }
        if (! $this->authorizer()->allows($user, $ability, $modelClass, $model)) {
            abort(403);
        }
    }

    private function authorizer(): FlatpackAuthorizer
    {
        return app(FlatpackAuthorizer::class);
    }
}
