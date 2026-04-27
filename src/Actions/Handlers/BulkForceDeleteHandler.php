<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackBulkActionContext;
use Flatpack\Services\Lists\BulkForceDeleteService;
use Illuminate\Contracts\Auth\Authenticatable;

final class BulkForceDeleteHandler extends FlatpackBulkActionHandler
{
    public function authorize(Authenticatable $user, string $modelClass): bool
    {
        return $this->canPerformAction(
            user: $user,
            ability: 'forceDelete',
            modelClass: $modelClass
        );
    }

    public function handle(FlatpackBulkActionContext $context): int
    {
        return app(BulkForceDeleteService::class)->forceDelete(
            modelClass: $context->modelClass,
            records: $context->records,
            schema: $context->schema,
            search: $context->search,
            filters: $context->filters,
        );
    }
}
