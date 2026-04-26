<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackBulkActionContext;
use Flatpack\Services\Lists\BulkDeleteService;
use Illuminate\Contracts\Auth\Authenticatable;

final class BulkDeleteHandler extends FlatpackBulkActionHandler
{
    public function authorize(Authenticatable $user, string $modelClass): bool
    {
        return $this->canPerformAction(
            user: $user,
            ability: 'delete',
            modelClass: $modelClass
        );
    }

    public function handle(FlatpackBulkActionContext $context): int
    {
        return app(BulkDeleteService::class)->delete(
            modelClass: $context->modelClass,
            records: $context->records,
            schema: $context->schema,
            search: $context->search,
            filters: $context->filters,
        );
    }
}
