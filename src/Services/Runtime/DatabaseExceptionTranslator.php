<?php

declare(strict_types=1);

namespace Flatpack\Services\Runtime;

use Flatpack\Support\DatabaseConstraintParser;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Throwable;

final class DatabaseExceptionTranslator
{
    public function toUserFacingValidationException(Throwable $exception): ValidationException
    {
        $message = 'This change could not be completed.';
        if ($exception instanceof MassAssignmentException) {
            $message = config('app.debug')
                ? $exception->getMessage()
                : 'This field is not writable for this model.';
        } elseif ($exception instanceof QueryException) {
            $validationError = $this->databaseValidationError($exception);
            if ($validationError !== null) {
                return ValidationException::withMessages([
                    $validationError['field'] => $validationError['message'],
                ]);
            }
        } elseif (config('app.debug')) {
            $message = $exception->getMessage() !== ''
                ? $exception->getMessage()
                : $message;
        }

        $messages = [
            'flatpack' => $message,
        ];
        if (config('app.debug')) {
            $messages['flatpack_exception'] = $exception::class;
            $messages['flatpack_exception_message'] = $exception->getMessage() !== ''
                ? $exception->getMessage()
                : '(no message; see server log for stack trace)';
        }

        return ValidationException::withMessages($messages);
    }

    /**
     * @return array{field: string, message: string}|null
     */
    private function databaseValidationError(QueryException $exception): ?array
    {
        $field = DatabaseConstraintParser::notNullColumn($exception);
        if ($field !== null) {

            return [
                'field' => $field,
                'message' => sprintf('%s is required.', str_replace('_', ' ', ucfirst($field))),
            ];
        }

        $field = DatabaseConstraintParser::uniqueColumn($exception);
        if ($field !== null) {

            return [
                'field' => $field,
                'message' => sprintf('%s must be unique.', str_replace('_', ' ', ucfirst($field))),
            ];
        }

        return null;
    }
}
