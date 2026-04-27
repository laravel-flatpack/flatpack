<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackBulkActionContext;
use Flatpack\Services\Lists\BulkRestoreService;
use Illuminate\Contracts\Auth\Authenticatable;

final class BulkRestoreHandler extends FlatpackBulkActionHandler
{
    public function authorize(Authenticatable $user, string $modelClass): bool
    {
        return $this->canPerformAction(
            user: $user,
            ability: 'restore',
            modelClass: $modelClass
        );
    }

    public function handle(FlatpackBulkActionContext $context): int
    {
        return app(BulkRestoreService::class)->restore(
            modelClass: $context->modelClass,
            records: $context->records,
            schema: $context->schema,
            search: $context->search,
            filters: $context->filters,
        );
    }
}
