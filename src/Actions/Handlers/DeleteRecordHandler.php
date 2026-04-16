<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Contracts\Actions\FlatpackAction;
use Illuminate\Database\Eloquent\Model;

final class DeleteRecordHandler implements FlatpackAction
{
    public function handle(FlatpackActionContext $context): mixed
    {
        $model = $context->model;
        if (! $model instanceof Model) {
            return null;
        }

        return $model->delete();
    }
}
