<?php

declare(strict_types=1);

use Flatpack\Support\DatabaseConstraintParser;
use Illuminate\Database\QueryException;

it('parses NOT NULL column from SQLite messages', function (): void {
    $e = new QueryException('sqlite', 'x', [], new Exception('NOT NULL constraint failed: posts.title'));
    expect(DatabaseConstraintParser::notNullColumn($e))->toBe('title');
});

it('parses NOT NULL column from PostgreSQL messages', function (): void {
    $e = new QueryException('pgsql', 'x', [], new Exception(
        'null value in column "email" of relation "users" violates not-null constraint',
    ));
    expect(DatabaseConstraintParser::notNullColumn($e))->toBe('email');
});

it('parses NOT NULL column from MySQL messages', function (): void {
    $e = new QueryException('mysql', 'x', [], new Exception("Column 'slug' cannot be null"));
    expect(DatabaseConstraintParser::notNullColumn($e))->toBe('slug');
});

it('returns null for unrecognized NOT NULL messages', function (): void {
    $e = new QueryException('sqlite', 'x', [], new Exception('something else'));
    expect(DatabaseConstraintParser::notNullColumn($e))->toBeNull();
});

it('parses UNIQUE column from SQLite messages', function (): void {
    $e = new QueryException('sqlite', 'x', [], new Exception('UNIQUE constraint failed: posts.slug'));
    expect(DatabaseConstraintParser::uniqueColumn($e))->toBe('slug');
});

it('parses UNIQUE column from MySQL duplicate entry messages', function (): void {
    $e = new QueryException('mysql', 'x', [], new Exception(
        "Duplicate entry 'a' for key 'posts.slug'",
    ));
    expect(DatabaseConstraintParser::uniqueColumn($e))->toBe('slug');
});

it('parses UNIQUE column from PostgreSQL Key messages', function (): void {
    $e = new QueryException('pgsql', 'x', [], new Exception(
        'duplicate key value violates unique constraint "posts_slug_unique" Key (slug)=(x) already exists.',
    ));
    expect(DatabaseConstraintParser::uniqueColumn($e))->toBe('slug');
});

it('returns null for unrecognized UNIQUE messages', function (): void {
    $e = new QueryException('sqlite', 'x', [], new Exception('no unique here'));
    expect(DatabaseConstraintParser::uniqueColumn($e))->toBeNull();
});
