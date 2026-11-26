<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * The user must choose a new password before doing anything else.
 */
final class PasswordChangeRequired extends DomainError
{
    public function errorCode(): string
    {
        return 'PASSWORD_CHANGE_REQUIRED';
    }

    public function httpStatus(): int
    {
        return 403;
    }

    protected function defaultMessage(): string
    {
        return 'Please choose a new password to continue.';
    }
}
