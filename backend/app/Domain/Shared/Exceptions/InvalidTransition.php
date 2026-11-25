<?php

namespace App\Domain\Shared\Exceptions;

/**
 * A state transition was attempted that the lifecycle rules do not allow (SRS §6).
 */
final class InvalidTransition extends DomainError
{
    public function errorCode(): string
    {
        return 'INVALID_TRANSITION';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    protected function defaultMessage(): string
    {
        return 'This record is not in a state that allows this action.';
    }
}
