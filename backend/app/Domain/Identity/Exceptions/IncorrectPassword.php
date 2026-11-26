<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * The current password given when changing a password is wrong.
 */
final class IncorrectPassword extends DomainError
{
    public function errorCode(): string
    {
        return 'INCORRECT_PASSWORD';
    }

    public function httpStatus(): int
    {
        return 422;
    }

    protected function defaultMessage(): string
    {
        return 'Your current password is not correct.';
    }
}
