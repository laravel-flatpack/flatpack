<?php

declare(strict_types=1);

namespace Flatpack\Actions;

use Flatpack\Services\Runtime\ActionRuntime;
use Illuminate\Auth\Access\AuthorizationException;
use Throwable;

final readonly class EntityActionExecutor
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
     * @throws \Illuminate\Validation\ValidationException
     */
    public function execute(callable $execute): mixed
    {
        try {
            return $execute();
        } catch (AuthorizationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw $this->actions->toUserFacingValidationException($exception);
        }
    }
}
