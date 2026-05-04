<?php

declare(strict_types=1);

use Flatpack\Services\Runtime\DatabaseExceptionTranslator;
use Flatpack\Support\ValidationMessages;
use Flatpack\Tests\TestCase;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

uses(TestCase::class);

test('maps NOT NULL constraint to ValidationMessages required format', function (): void {
    $translator = new DatabaseExceptionTranslator;
    $previous = new PDOException('NOT NULL constraint failed: posts.email_address');
    $queryException = new QueryException('sqlite', 'insert into posts ...', [], $previous);

    $e = $translator->toUserFacingValidationException($queryException);

    expect($e)->toBeInstanceOf(ValidationException::class);
    $messages = $e->errors();
    expect($messages)->toHaveKey('email_address')
        ->and($messages['email_address'][0])->toBe(ValidationMessages::required('email_address'));
});

test('maps MassAssignmentException to fillable message when app.debug is false', function (): void {
    config(['app.debug' => false]);

    $translator = new DatabaseExceptionTranslator;
    $e = $translator->toUserFacingValidationException(
        new MassAssignmentException('Add [secret] to fillable'),
    );

    expect($e->errors()['flatpack'][0])->toBe('This field is not writable for this model.');
});

test('maps MassAssignmentException to exception message when app.debug is true', function (): void {
    config(['app.debug' => true]);

    $translator = new DatabaseExceptionTranslator;
    $e = $translator->toUserFacingValidationException(
        new MassAssignmentException('Add [secret] to fillable'),
    );

    expect($e->errors()['flatpack'][0])->toContain('secret');
});

test('maps generic exceptions using debug metadata when app.debug is true', function (): void {
    config(['app.debug' => true]);

    $translator = new DatabaseExceptionTranslator;
    $e = $translator->toUserFacingValidationException(new \RuntimeException('boom'));

    expect($e->errors())->toHaveKeys(['flatpack', 'flatpack_exception', 'flatpack_exception_message'])
        ->and($e->errors()['flatpack'][0])->toBe('boom');
});

test('uses generic message when app.debug is false for unknown exceptions', function (): void {
    config(['app.debug' => false]);

    $translator = new DatabaseExceptionTranslator;
    $e = $translator->toUserFacingValidationException(new \RuntimeException('hidden'));

    expect($e->errors()['flatpack'][0])->toBe('This change could not be completed.')
        ->and($e->errors())->not->toHaveKey('flatpack_exception');
});

test('maps UNIQUE constraint to ValidationMessages unique format', function (): void {
    $translator = new DatabaseExceptionTranslator;
    $previous = new PDOException('UNIQUE constraint failed: users.slug');
    $queryException = new QueryException('sqlite', 'insert into users ...', [], $previous);

    $e = $translator->toUserFacingValidationException($queryException);

    expect($e)->toBeInstanceOf(ValidationException::class);
    $messages = $e->errors();
    expect($messages)->toHaveKey('slug')
        ->and($messages['slug'][0])->toBe(ValidationMessages::unique('slug'));
});
