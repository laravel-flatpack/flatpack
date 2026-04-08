<?php

declare(strict_types=1);

namespace Flatpack\Contracts\Composition;

use RuntimeException;

final class CompositionNotFoundException extends RuntimeException
{
    public static function forEntity(string $entity, string $type, ?string $path = null): self
    {
        $message = "Composition not found for entity [{$entity}] type [{$type}].";

        if ($path !== null) {
            $message .= " Path: {$path}";
        }

        return new self($message);
    }
}
