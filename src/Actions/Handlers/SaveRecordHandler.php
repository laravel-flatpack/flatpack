<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Contracts\Actions\FlatpackAction;

final class SaveRecordHandler implements FlatpackAction
{
    public function handle(FlatpackActionContext $context): mixed
    {
        // Persist model from request + composition (implemented in a later iteration)
        return null;
    }
}
