<?php

declare(strict_types=1);

namespace Flatpack\Support\Exceptions;

use RuntimeException;

final class WidgetRuntimeException extends RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
