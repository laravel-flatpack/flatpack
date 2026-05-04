<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\BulkActionContext;
use Flatpack\Actions\BulkActionHandler;
use Illuminate\Contracts\Auth\Authenticatable;

final class BulkForceDeleteHandler extends BulkActionHandler
{
    public function authorize(Authenticatable $user, string $modelClass): bool
    {
        return $this->canPerformAction(
            user: $user,
            ability: 'forceDelete',
            modelClass: $modelClass
        );
    }

    public function handle(BulkActionContext $context): int
    {
        return $this->runBulkForceDelete($context);
    }
}
