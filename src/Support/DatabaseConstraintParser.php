<?php

declare(strict_types=1);

namespace Flatpack\Support;

use Illuminate\Database\QueryException;

final class DatabaseConstraintParser
{
    public static function notNullColumn(QueryException $exception): ?string
    {
        $message = $exception->getMessage();

        // SQLite
        if (preg_match('/NOT NULL constraint failed: [^.]+\.([a-zA-Z0-9_]+)/', $message, $matches) === 1) {
            return $matches[1];
        }

        // PostgreSQL: 'null value in column "col" of relation "tbl" violates not-null constraint'
        if (preg_match('/null value in column "([^"]+)"/i', $message, $matches) === 1) {
            return $matches[1];
        }

        // MySQL: "Column 'col' cannot be null"
        if (preg_match("/Column '([^']+)' cannot be null/i", $message, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    public static function uniqueColumn(QueryException $exception): ?string
    {
        $message = $exception->getMessage();

        // SQLite
        if (preg_match('/UNIQUE constraint failed: [^.]+\.([a-zA-Z0-9_]+)/', $message, $matches) === 1) {
            return $matches[1];
        }

        // MySQL: "Duplicate entry 'val' for key 'table.col'"
        if (preg_match('/Duplicate entry .* for key .*\.([a-zA-Z0-9_]+)\'?/', $message, $matches) === 1) {
            return $matches[1];
        }

        // PostgreSQL: "duplicate key value violates unique constraint … Key (col)=(val) already exists."
        if (preg_match('/Key \(([^)]+)\)=/s', $message, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
