<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Contracts\Actions\FlatpackAction;

final class EditRecordHandler implements FlatpackAction
{
    public function handle(FlatpackActionContext $context): mixed
    {
        if ($context->model === null) {
            throw new \Exception('Model not found');
        }

        return redirect()->route('flatpack.form.edit', [
            'entity' => $context->entity,
            'record' => $context->model->getKey(),
        ]);
    }
}
