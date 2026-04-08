<?php

declare(strict_types=1);

namespace Flatpack\Contracts\Actions;

use Flatpack\Actions\FlatpackActionContext;

interface FlatpackAction
{
    public function handle(FlatpackActionContext $context): mixed;
}
