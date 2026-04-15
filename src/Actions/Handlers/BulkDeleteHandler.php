<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackBulkActionContext;
use Flatpack\Contracts\Actions\FlatpackBulkAction;
use Flatpack\Lists\BulkDeleteService;

final readonly class BulkDeleteHandler implements FlatpackBulkAction
{
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
