<?php

declare(strict_types=1);

namespace Flatpack\Actions;

use Flatpack\Services\Runtime\ActionRuntime;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class ActionExecutor
{
    public function __construct(
        private ActionRuntime $actions,
    ) {}

    /**
     * @template TResult
     *
     * @param  callable(): TResult  $execute
     * @return TResult
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(callable $execute): mixed
    {
        try {
            return $execute();
        } catch (AuthorizationException|ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            throw $this->actions->toUserFacingValidationException($exception);
        }
    }
}
