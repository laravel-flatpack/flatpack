<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Contracts\Actions\FlatpackAction;

final class CreateRecordHandler implements FlatpackAction
{
    public function handle(FlatpackActionContext $context): mixed
    {
        return redirect()->route('flatpack.entities.create', [
            'entity' => $context->entity,
        ]);
    }
}
