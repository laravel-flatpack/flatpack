<?php

declare(strict_types=1);

namespace Flatpack\Support;

use Illuminate\Database\QueryException;

final class DatabaseConstraintParser
{
    public static function notNullColumn(QueryException $exception): ?string
    {
        $message = $exception->getMessage();

        if (preg_match('/NOT NULL constraint failed: [^.]+\.([a-zA-Z0-9_]+)/', $message, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    public static function uniqueColumn(QueryException $exception): ?string
    {
        $message = $exception->getMessage();

        if (preg_match('/UNIQUE constraint failed: [^.]+\.([a-zA-Z0-9_]+)/', $message, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/Duplicate entry .* for key .*\.([a-zA-Z0-9_]+)\'?/', $message, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
